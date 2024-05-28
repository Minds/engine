<?php

namespace Minds\Controllers\Cli;

use DateTime;
use Minds\Cli;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Di\Di;
use Minds\Core\Security\ACL;
use Minds\Exceptions\CliException;
use Minds\Interfaces;
use Monolog\Logger as MonologLogger;

class Boost extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(private ?BoostManager $boostManager = null)
    {
        $this->boostManager ??= Di::_()->get(BoostManager::class);
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

        if ($this->getOpt('dry-run')) {
            $rankingManager->setDryRun(true);
        }

        if ($fromUnixTs = $this->getOpt('from-unix-ts')) {
            $rankingManager->setFromUnixTs((int) $fromUnixTs);
        }

        while (true) {
            $rankingManager->calculateRanks();

            // There is a memory leak, uncomment to log
            // $mem = memory_get_usage();
            // Di::_()->get('Logger')->info(round($mem/1048576, 2) . 'mb used');

            if ($sleep = $this->getOpt('sleep')) {
                sleep($sleep);
            }
        }

        $this->out('Done');
    }

    public function processExpired()
    {
        try {
            $this->boostManager->processExpiredApprovedBoosts();
        } catch (\Exception $e) {
            var_dump($e);
        }
    }
    
    /**
     * To be run as a cronjob periodically.
     * Will scan through onchain boosts and mark as pending (or approve)
     * @example
     * - `php cli.php Boost processOnchain`
     * @return void
     */
    public function processOnchain()
    {
        Di::_()->get('Config')->set('min_log_level', MonologLogger::INFO);
        /** @var Core\Boost\V3\Onchain\OnchainBoostBackgroundJob */
        $job = Di::_()->get(Core\Boost\V3\Onchain\OnchainBoostBackgroundJob::class);
        $job->run();
    }

    /**
     * Manually approve a payment
     * @example
     * - php cli.php Boost approveBoost --boostGuid=100000000000000000
     * @return void
     */
    public function approveBoost()
    {
        $boostGuid = $this->getOpt('boostGuid');
        $this->boostManager->approveBoost($boostGuid);
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

        if (!$boost = $this->boostManager->getBoostByGuid($boostGuid)) {
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

    /**
     * Reject a boost by GUID
     * @param string entityGuid - entity_guid of the boost.
     * @param string status - numerical index of BoostStatus we want to update for.
     * @example
     * - php cli.php Boost forceRejectStateByEntityGuid --entityGuid=100000000000000000 --reason=5
     * @return void
     */
    public function forceRejectByEntityGuid(): void
    {
        $entityGuid = $this->getOpt('entityGuid');
        $reason = $this->getOpt('reason') ?? BoostRejectionReason::REPORT_UPHELD;

        if (!$entityGuid) {
            $this->out('Entity GUID must be provided');
            return;
        }

        if ($this->boostManager->forceRejectByEntityGuid(
            entityGuid: $entityGuid,
            reason: $reason,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )) {
            $this->out("Updated status to rejected for any boosts with entity guid: $entityGuid");
        } else {
            $this->out("An error has occurred updating status to rejected for any boosts with entity guid: $entityGuid");
        }
    }
}
