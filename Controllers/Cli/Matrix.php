<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;

class Matrix extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /** @var Core\Matrix\Manager */
    protected $manager;

    /** @var Core\Matrix\BlockListSync */
    protected $blockListSync;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');
        $this->manager = Di::_()->get('Matrix\Manager');
        $this->blockListSync = Di::_()->get('Matrix\BlockListSync');
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->out('See help');
    }

    public function syncUsers()
    {
        foreach ($this->manager->getAccounts() as $account) {
            $matrixId = $account->getId();

            /** @var User */
            $user = $this->entitiesBuilder->single($account->getUserGuid());

            $this->manager->syncAccount($user);

            $this->out("{$user->getGuid()}: $matrixId");
        }
    }

    public function syncUser()
    {
        /** @var User */
        $user = $this->entitiesBuilder->single($this->getOpt('user_guid'));

        try {
            $this->manager->syncAccount($user);

            $this->blockListSync->sync($user);
        } catch (\Exception $e) {
        }

        $this->out("{$user->getGuid()}");
    }
}
