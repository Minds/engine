<?php
namespace Minds\Core\ActivityPub\Factories;

use DateTime;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Minds\Core\ActivityPub\Builders\Objects\MindsActivityBuilder;
use Minds\Core\ActivityPub\Builders\Objects\MindsCommentBuilder;
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
        private readonly MindsActivityBuilder $mindsActivityBuilder,
        private readonly MindsCommentBuilder $mindsCommentBuilder,
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
     * @throws Exception
     */
    public function fromEntity(EntityInterface $entity): ObjectType
    {
        $object = match (get_class($entity)) {
            Activity::class => $this->mindsActivityBuilder->toActivityPubNote($entity),

            Comment::class => $this->mindsCommentBuilder->toActivityPubNote($entity),

            // TODO: Add docs to explain why this is needed - in short actors are a sub type of objects
            User::class => $this->actorFactory->fromEntity($entity),

            default => throw new NotImplementedException()
        };

        // If this is a 'reply', then cc in the owner of who we are replying to
        if ($object->inReplyTo ?? null) {
            $replyObject = $this->fromUri($object->inReplyTo);
            $object->cc[] = $replyObject->attributedTo;
        }

        return $object;
    }

    public function fromJson(array $json): ObjectType
    {
        if (in_array($json['type'], ActorFactory::ACTOR_TYPES, true)) {
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

        return match (get_class($object)) {
            NoteType::class => $object->content = $json['content']
        };
    }
}
