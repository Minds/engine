<?php
namespace Minds\Core\ActivityPub\Factories;

use DateTime;
use GuzzleHttp\Exception\ConnectException;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;

class ObjectFactory
{
    public function __construct(
        private Manager $manager,
        private Client $client,
        private ActorFactory $actorFactory,
    ) {
        
    }

    public function fromUri(string $uri): ObjectType
    {
        if ($this->manager->isLocalUri($uri)) {
            $entity = $this->manager->getEntityFromUri($uri);
            if (!$entity) {
                throw new NotFoundException();
            }
            return $this->fromEntity($entity);
        }

        try {
            $response = $this->client->request('GET', $uri);
            $json = json_decode($response->getBody()->getContents(), true);
        } catch (ConnectException $e) {
            throw new UserErrorException("Could not connect to $uri");
        }

        return $this->fromJson($json);
    }

    public function fromEntity(EntityInterface $entity): ObjectType
    {
        $actorUri = $this->manager->getBaseUrl() . 'users/' .$entity->getOwnerGuid();
        switch (get_class($entity)) {
            case Activity::class:
                /** @var Activity */
                $activity = $entity;
                
                $json = [
                    'id' => $actorUri . '/entities/' . $entity->getGuid(),
                    'type' => 'Note',
                    'content' => $activity->getMessage(),
                    'attributedTo' => $actorUri,
                    'to' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                    ],
                    'cc' => [
                        $actorUri . '/followers',
                    ],
                    'published' => date('c', $activity->getTimeCreated()),
                    'url' => $activity->getUrl(),
                ];

                // Is this a quote post
                if ($activity->isQuotedPost()) {
                    $json['inReplyTo'] = $this->manager->getUriFromGuid($activity->remind_object['guid']);
                }

                // Is this a remind
                // if ($activity->isRemind()) {
                //     $json['inReplyTo'] = $this->manager->getUriFromGuid($activity->remind_object['guid']);
                // }
                
                break;
            case Comment::class:
                /** @var Comment */
                $comment = $entity;

                $json = [
                    'id' => $actorUri . '/entities/' . $entity->getGuid(),
                    'type' => 'Note',
                    'content' => $comment->getBody(),
                    'attributedTo' => $actorUri,
                    'inReplyTo' => $this->manager->getUriFromGuid($comment->getParentGuid() ?: $comment->getEntityGuid()),
                    'to' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                    ],
                    'cc' => [
                        $actorUri . '/followers',
                    ],
                    'published' => date('c', $comment->getTimeCreated()),
                    'url' => $comment->getUrl(),
                ];
                break;
            default:
                throw new NotImplementedException();
        }

        // If this is a 'reply', then cc in the owner of who we are replying to
        if ($json['inReplyTo'] ?? null) {
            $replyObject = $this->fromUri($json['inReplyTo']);
            $json['cc'][] = $replyObject->attributedTo;
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

        if (isset($json['attributedTo'])) {
            $object->attributedTo = $json['attributedTo'];
        }

        if (isset($json['url'])) {
            $object->url = $json['url'];
        }

        if (isset($json['inReplyTo'])) {
            $object->inReplyTo = JsonLdHelper::getValueOrId($json['inReplyTo']);
        }

        switch (get_class($object)) {
            case NoteType::class:
                $object->content = $json['content'];
                break;
        }

        return $object;
    }
}
