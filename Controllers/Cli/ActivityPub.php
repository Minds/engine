<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\ActivityPub\Services;
use Minds\Core\ActivityPub\Services\EmitActivityService;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Entities\User;
use Minds\Interfaces;

class ActivityPub extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private EntitiesBuilder $entitiesBuilder;
    private EmitActivityService $emitActivityService;
    private ObjectFactory $objectFactory;
    private ActorFactory $actorFactory;

    public function __construct()
    {
        Di::_()->get('Config')
          ->set('min_log_level', 'INFO');

        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $this->emitActivityService = Di::_()->get(EmitActivityService::class);
        $this->objectFactory = Di::_()->get(ObjectFactory::class);
        $this->actorFactory = Di::_()->get(ActorFactory::class);
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    /**
     * @return void
     */
    public function exec(): void
    {
    }

    public function emitEntity()
    {
        $guid = $this->getOpt('guid');

        $entity = $this->entitiesBuilder->single($guid);
        $owner = $this->entitiesBuilder->single($entity->getOwnerGuid());
        if (!$owner instanceof User) {
            return;
        }
        $actor = $this->actorFactory->fromEntity($owner);


        $activity = new CreateType();
        $activity->actor = $actor;
        $activity->object = $this->objectFactory->fromEntity($entity);


        $this->emitActivityService->emitActivity($activity, $owner);

    }
}
