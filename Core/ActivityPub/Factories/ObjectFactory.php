<?php
namespace Minds\Core\ActivityPub\Factories;

use DateTime;
use GuzzleHttp\Exception\ConnectException;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Core\ActivityPub\Types\Object\DocumentType;
use Minds\Core\ActivityPub\Types\Object\ImageType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;

class ObjectFactory
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Client $client,
        private readonly ActorFactory $actorFactory,
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

    /**
     * @param EntityInterface $entity
     * @return ObjectType
     * @throws NotFoundException
     * @throws NotImplementedException
     * @throws UserErrorException
     * @throws \Minds\Exceptions\ServerErrorException
     */
    public function fromEntity(EntityInterface $entity): ObjectType
    {
        $actorUri = $this->manager->getBaseUrl() . 'users/' .$entity->getOwnerGuid();

        /**
         * If this is a remote entity, then we need to get the remote uri
         */
        if ($entity->getSource() === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            if ($uri = $this->manager->getUriFromEntity($entity)) {
                if (!$this->manager->isLocalUri($uri)) {
                    return $this->fromUri($uri);
                }
            } else {
                throw new NotFoundException("Found ActivityPub entity but could not resolve remote uri");
            }
        }

        switch (get_class($entity)) {
            case Activity::class:
                /** @var Activity */
                $activity = $entity;

                $json = [
                    'id' => $actorUri . '/entities/' . $entity->getUrn(),
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
                    $json['inReplyTo'] = $this->manager->getUriFromEntity($activity->getRemind());
                }

                // Is this a remind
                // if ($activity->isRemind()) {
                //     $json['inReplyTo'] = $this->manager->getUriFromEntity($activity->getRemind());
                // }

                // Any image attachments?
                if ($activity->hasAttachments() && $activity->getCustomType() === 'batch') {
                    $attachments = [];
                    foreach ($activity->getCustomData() as $row) {
                        $attachment = [
                            'type' => 'Document',
                            'mediaType' => 'image/jpeg',
                            'url' => $row['src'],
                        ];
                        if ($row['width']) {
                            $attachment['width'] = $row['width'];
                        }
                        if ($row['height']) {
                            $attachment['height'] = $row['height'];
                        }
                        $attachments[] = $attachment;
                    }
                    $json['attachment'] = $attachments;
                }

                break;
            case Comment::class:
                /** @var Comment */
                $comment = $entity;

                if ($comment->getParentGuid()) {
                    $parentUrn = $comment->getParentUrn();
                } else {
                    $parentUrn = 'urn:entity:' . $comment->getEntityGuid();
                }

                /**
                 * Get the uri of what we are replying to
                 */
                $replyToUri = $this->manager->getUriFromUrn($parentUrn);

                $json = [
                    'id' => $actorUri . '/entities/' . $entity->getUrn(),
                    'type' => 'Note',
                    'content' => $comment->getBody(),
                    'attributedTo' => $actorUri,
                    'inReplyTo' => $replyToUri,
                    'to' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                    ],
                    'cc' => [
                        $actorUri . '/followers',
                    ],
                    'published' => date('c', (int) $comment->getTimeCreated()),
                    'url' => $comment->getUrl(),
                ];

                // Any images?
                $attachments = [];
                if ($comment->getAttachments() && $comment->getAttachments()['custom_type'] === 'image') {
                    $row = json_decode($comment->getAttachments()['custom_data'], true);
                    $attachment = [
                        'type' => 'Document',
                        'mediaType' => 'image/jpeg',
                        'url' => $row['src'],
                    ];
                    if ($row['width']) {
                        $attachment['width'] = $row['width'];
                    }
                    if ($row['height']) {
                        $attachment['height'] = $row['height'];
                    }
                    $attachments[] = $attachment;
                    $json['attachment'] = $attachments;
                }
                break;

            case User::class:
                return $this->actorFactory->fromEntity($entity);

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
        if (isset(ActorFactory::ACTOR_TYPES[$json['type']])) {
            return $this->actorFactory->fromJson($json);
        }

        $object = match ($json['type']) {
            'Note' => new NoteType(),
            'Image' => new ImageType(),
            'Document' => new DocumentType(),
            default => new NotImplementedException(),
        };

        if (isset($json['id'])) {
            $object->id = $json['id'];
        }

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

        if (isset($json['attachment'])) {
            $object->attachment = [];

            foreach ($json['attachment'] as $attachment) {
                try {
                    $object->attachment[] = $this->fromJson($attachment);
                } catch (NotImplementedException $e) {
                    // Can not support yet, will just skip
                }
            }
        }

        if (isset($json['mediaType'])) {
            $object->mediaType = $json['mediaType'];
        }

        if (isset($json['width'])) {
            $object->width = $json['width'];
        }

        if (isset($json['height'])) {
            $object->height = $json['height'];
        }

        switch (get_class($object)) {
            case NoteType::class:
                $object->content = $json['content'];
                break;
        }

        return $object;

    }
}
