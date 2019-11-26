<?php

namespace Minds\Controllers\Cli;

use DateTime;
use Elasticsearch\ClientBuilder;
use Minds\Cli;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Helpers\Flags;
use Minds\Interfaces;
use Minds\Core\Rewards\Contributions\UsersIterator;

class BoostCampaigns extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
    }

    public function start()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $offset = $this->getOpt('offset') ?? null;
        $type = $this->getOpt('type') ?? 'newsfeed';

        $ownerGuid = $this->getOpt('ownerGuid') ?? null;

        /** @var Core\Boost\Campaigns\Manager $manager */
        $manager = Di::_()->get('Boost\Campaigns\Manager');

        $iterator = (new Core\Boost\Campaigns\Iterator())
            ->setOffset($offset)
            ->setState(Core\Boost\Campaigns\Campaign::STATUS_CREATED)
            ->setOwnerGuid($ownerGuid)
            ->setType($type);

        foreach ($iterator as $campaign) {
            try {
                $manager->start($campaign);
            } catch (\Exception $e) {
                error_log(get_class($e) . ': ' . $e->getMessage());
                continue;
            }
        }
    }
}
