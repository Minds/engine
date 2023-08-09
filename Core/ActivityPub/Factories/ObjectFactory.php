<?php
namespace Minds\Core\ActivityPub\Factories;

use DateTime;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use NotImplementedException;

class ObjectFactory
{
    public function __construct(
        private Manager $manager
    ) {
        
    }
    public function fromEntity(EntityInterface $entity): ObjectType
    {
        switch (get_class($entity)) {
            case Activity::class:
                /** @var Activity */
                $activity = $entity;
                $actorUri = $this->manager->getBaseUrl() . $entity->getOwnerGuid() . '/';
                $json = [
                    'id' => $actorUri . 'entities/' . $entity->getGuid(),
                    'type' => 'Note',
                    'content' => $activity->getMessage(),
                    'to' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                    ],
                    'cc' => [
                        $actorUri . '/followers',
                    ],
                    'published' => date('c', $activity->getTimeCreated()),
                ];
                break;
            default:
                throw new NotImplementedException();
        }
       
        return $this->fromJson($json);
    }

    public function fromJson(array $json): ObjectType
    {
        $object = match ($json['type']) {
            'Note' => new NoteType(),
            default => new NotImplementedException(),
        };

        $object->id = $json['id'];

        if (isset($json['to'])) {
            $object->to = $json['to'];
        }

        if (isset($json['cc'])) {
            $object->cc = $json['cc'];
        }

        if (isset($json['published'])) {
            $object->published = new DateTime($json['published']);
        }

        switch (get_class($object)) {
            case NoteType::class:
                $object->content = $json['content'];
                break;
        }

        return $object;
    }
}
