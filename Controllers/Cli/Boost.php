<?php

namespace Minds\Controllers\Cli;

use DateTime;
use Minds\Cli;
use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Security\ACL;
use Minds\Exceptions\CliException;
use Minds\Interfaces;
use Monolog\Logger as MonologLogger;

class Boost extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->out('Usage: cli boost [*]');
    }

    /**
     *
     */
    public function syncLiquiditySpot()
    {
        Di::_()->get('Config')->set('min_log_level', MonologLogger::INFO);

        $liquditySpotManager = Di::_()->get('Boost\LiquiditySpot\Manager');

        while (true) {
            $liquditySpotManager->sync();
            $this->out('Synced. Will now sleep for 5 seconds');
            sleep(5);
        }
        $this->out('done');
    }

    /**
     * Run continously to ensure boosts are correctly ranked and served
     * `php cli.php Boost rank`
     */
    public function rank()
    {
        Di::_()->get('Config')->set('min_log_level', MonologLogger::INFO);

        /** @var Core\Boost\V3\Ranking\Manager */
        $rankingManager = Di::_()->get(Core\Boost\V3\Ranking\Manager::class);

        while (true) {
            $rankingManager->calculateRanks();

            // There is a memory leak, uncomment to log
            // $mem = memory_get_usage();
            // Di::_()->get('Logger')->info(round($mem/1048576, 2) . 'mb used');

            sleep(1);
        }

        $this->out('Done');
    }

    /**
     * Call this function to sync views to summaries
     * `php cli.php Boost syncViews`
     */
    public function syncViews()
    {
        Di::_()->get('Config')->set('min_log_level', MonologLogger::INFO);

        ACL::_()->setIgnore(true);

        /** @var Core\Boost\V3\Summaries\Manager */
        $summariesManager = Di::_()->get(Core\Boost\V3\Summaries\Manager::class);

        $summariesManager->sync(new DateTime('midnight'));

        $this->out('Done');
    }

    /**
     * use for testing in your local environments only!
     * `php cli.php Boost simulateViews`
     */
    protected function simulateViews()
    {
        $viewsManager = new Core\Analytics\Views\Manager();

        /** @var Core\Boost\V3\Manager */
        $boostManager =  Di::_()->get(Core\Boost\V3\Manager::class);

        foreach ($boostManager->getBoosts(
            targetStatus: BoostStatus::APPROVED,
            limit: 1,
            orderByRanking: true
        ) as $boost) {
            $viewsManager->record(
                (new Core\Analytics\Views\View())
                    ->setEntityUrn("urn:entity:" . $boost->getEntityGuid())
                    ->setOwnerGuid((string) 0)
                    ->setClientMeta([])
                    ->setCampaign('urn:boost:newsfeed:' . $boost->getGuid())
            );
            //$this->out('View for ' . $boost->getGuid());
        }
    }

    public function processExpired()
    {
        /**
         * @var Core\Boost\V3\Manager $boostManager
         */
        $boostManager = Di::_()->get(Core\Boost\V3\Manager::class);

        $boostManager->processExpiredApprovedBoosts();
    }

    /**
     * Trigger an action event for a given boost. Does NOT mark the boost states, just pushes to action event topic.
     * @param string boostGuid - guid of the boost.
     * @example
     * - php cli.php Boost triggerActionEvent --boostGuid=100000000000000000 --eventType='complete'
     * - php cli.php Boost triggerActionEvent --boostGuid=100000000000000000 --eventType='approve'
     * - php cli.php Boost triggerActionEvent --boostGuid=100000000000000000 --eventType='create'
     * - php cli.php Boost triggerActionEvent --boostGuid=100000000000000000 --eventType='reject' --rejectionReason=3
     * @return void
     */
    public function triggerActionEvent(): void
    {
        $boostGuid = $this->getOpt('boostGuid');
        $eventType = $this->getOpt('eventType') ?? 'complete';
        $rejectionReason = $this->getOpt('rejectionReason') ?? false;

        if (!$boostGuid) {
            $this->out('Boost GUID must be provided');
            return;
        }

        if ($eventType === 'reject' && !$rejectionReason) {
            $this->out('A rejectionReason parameter must be provided for reject events');
            return;
        }

        /** @var Core\Boost\V3\Manager */
        $boostManager =  Di::_()->get(Core\Boost\V3\Manager::class);

        if (!$boost = $boostManager->getBoostByGuid($boostGuid)) {
            $this->out('Boost not found');
            return;
        }

        /** @var ActionEventDelegate */
        $actionEventDelegate =  Di::_()->get(ActionEventDelegate::class);

        switch ($eventType) {
            case 'create':
                $actionEventDelegate->onCreate($boost);
                break;
            case 'complete':
                $actionEventDelegate->onComplete($boost);
                break;
            case 'accept':
            case 'approve':
                $actionEventDelegate->onApprove($boost);
                break;
            case 'reject':
                $actionEventDelegate->onReject($boost, $rejectionReason);
                break;
            default:
                throw new CliException('Unknown event type provided. Must be: complete, accept, create or reject');
        }
        
        $this->out("Completion notice dispatched for boost: $boostGuid");
    }
}
