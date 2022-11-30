<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Entities;
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

    public function rank()
    {
        /** @var Core\Boost\V3\Ranking\Manager */
        $rankingManager = Di::_()->get('Boost\V3\Ranking\Manager');

        while (true) {
            $this->simulateViews();

            $rankingManager->calculateRanks();

            // There is a memory leak, uncomment to log
            // $mem = memory_get_usage();
            // Di::_()->get('Logger')->info(round($mem/1048576, 2) . 'mb used');
    
            sleep(1);
        }

        $this->out('Done');
    }

    protected function simulateViews()
    {
        $viewsManager = new Core\Analytics\Views\Manager();

        /** @var Core\Boost\V3\Ranking\Repository */
        $boostRankingRepo =  Di::_()->get('Boost\V3\Ranking\Repository');
        foreach ($boostRankingRepo->getBoostShareRatios() as $boostShareRatio) {
            $viewsManager->record(
                (new Core\Analytics\Views\View())
                    ->setEntityUrn("urn:entity:" . $boostShareRatio->getGuid())
                    ->setOwnerGuid((string) 0)
                    ->setClientMeta([])
                    ->setCampaign('urn:boost:newsfeed:' . $boostShareRatio->getGuid())
            );
            //$this->out('View for ' . $boost->getGuid());
        }
    }
}
