<?php
namespace Minds\Core\ActivityPub\Factories;

use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\UndoType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\Di\Di;
use NotImplementedException;

class ActivityFactory
{
    public function __construct(
        protected ActorFactory $actorFactory,
        protected ObjectFactory $objectFactory,
    ) {
        
    }

    public function fromJson(array $json, AbstractActorType $actor): ActivityType
    {
        $activity = match ($json['type']) {
            'Create' => new CreateType(),
            'Follow' => new FollowType(),
            'Undo' => new UndoType(),
            default => throw new NotImplementedException(),
        };

        // Must
        $activity->id = $json['id'];
        $activity->actor = $actor;

        $activity->object = match (get_class($activity)) {
            FollowType::class => $this->actorFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            UndoType::class => $this->fromJson($json['object'], $actor),
            default => $this->objectFactory->fromJson($json['object']),
        };

        return $activity;
    }
}
