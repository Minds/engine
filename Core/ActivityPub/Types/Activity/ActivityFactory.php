<?php
namespace Minds\Core\ActivityPub\Types\Activity;

use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Core\ActivityPub\Types\Object\ObjectFactory;
use Minds\Core\Di\Di;
use NotImplementedException;

class ActivityFactory
{
    public static function build(array $json, AbstractActorType $actor): ActivityType
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
            FollowType::class => Di::_()->get(Manager::class)->uriToActor(JsonLdHelper::getValueOrId($json['object'])),
            UndoType::class => static::build($json['object'], $actor),
            default => ObjectFactory::build($json['object']),
        };

        return $activity;
    }
}