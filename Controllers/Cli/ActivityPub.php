<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Enums\ActivityFactoryOpEnum;
use Minds\Core\ActivityPub\Factories\ActivityFactory;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\ActivityPub\Services;
use Minds\Core\ActivityPub\Services\EmitActivityService;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Entities\User;
use Minds\Interfaces;

class ActivityPub extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private Manager $manager;
    private EntitiesBuilder $entitiesBuilder;
    private EmitActivityService $emitActivityService;
    private ObjectFactory $objectFactory;
    private ActorFactory $actorFactory;
    private ActivityFactory $activityFactory;
    private ProcessActorService $processActorService;

    public function __construct()
    {
        Di::_()->get('Config')
          ->set('min_log_level', 'INFO');

        $this->manager = Di::_()->get(Manager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $this->emitActivityService = Di::_()->get(EmitActivityService::class);
        $this->objectFactory = Di::_()->get(ObjectFactory::class);
        $this->actorFactory = Di::_()->get(ActorFactory::class);
        $this->activityFactory = Di::_()->get(ActivityFactory::class);
        $this->processActorService = Di::_()->get(ProcessActorService::class);
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

    /**
     * Dev tool to re-emit a specific entity
     */
    public function emitEntity()
    {
        $guid = $this->getOpt('guid');

        $entity = $this->entitiesBuilder->single($guid);
        $owner = $this->entitiesBuilder->single($entity->getOwnerGuid());
        if (!$owner instanceof User) {
            return;
        }
    
        $activity = $this->activityFactory->fromEntity(ActivityFactoryOpEnum::CREATE, $entity, $owner);

        $this->emitActivityService->emitActivity($activity, $owner);
    }

    /**
     * Syncs all ActivityPub users profiles (will update avatars, names, descriptions)
     */
    public function syncUsers()
    {
        foreach ($this->manager->getActorEntities() as $user) {
            $this->out("Syncing {$user->getGuid()}");

            // Fetch their latest account
            $actor = $this->actorFactory->fromEntity($user);

            try {
                // Reprocess the user
                $this->processActorService
                    ->withActor($actor)
                    ->process();
            } catch (\Exception $e) {
                $this->out($e->getMessage());
            }
        }
    }
}
