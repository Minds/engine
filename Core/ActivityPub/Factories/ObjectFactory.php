<?php
namespace Minds\Core\ActivityPub\Factories;

use DateTime;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Helpers\ContentParserBuild;
use Minds\Core\ActivityPub\Helpers\ContentParserBuilder;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Core\ActivityPub\Types\Core\SourceType;
use Minds\Core\ActivityPub\Types\Link\MentionType;
use Minds\Core\ActivityPub\Types\Object\DocumentType;
use Minds\Core\ActivityPub\Types\Object\ImageType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\FederatedEntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Minds\Core\ActivityPub\Exceptions\NotImplementedException;
use Minds\Exceptions\ServerErrorException;

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

            if (!is_array($json)) {
                throw new UserErrorException("Bad response from server");
            }
        } catch (ConnectException $e) {
            throw new UserErrorException("Could not connect to $uri");
        } catch (ClientException|ServerException $e) {
            throw new ServerErrorException("Unable to fetch $uri. " . $e->getMessage());
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

        if (!$entity instanceof FederatedEntityInterface) {
            throw new NotImplementedException();
        }

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

                // Is this a remind, if so, we want to get the orignial entity
                if ($activity->isRemind()) {
                    $activity = $activity->getRemind();
                    if (!$activity) {
                        throw new NotFoundException("Reminded content cant be found");
                    }
                }

                $content = '';

                if ($activity->isPayWall()) {
                    $title = $activity->getTitle();
                    if ($title) {
                        $content .= $title . "\n";
                    }
                    $content .= $activity->getURL();
                } else {
                    if ($activity->hasAttachments() && $activity->getTitle()) {
                        $content .= $activity->getTitle() . "\n";
                    }

                    $content .= $activity->getMessage() ?: '';

                    // Rich embed? Are we including our link?
                    if ($activity->perma_url) {
                        $urls = ContentParserBuilder::getUrls($content);
                        if (!count($urls)) {
                            // No links found in the post, so we will append the permaurl
                            if ($content) {
                                $content .= "\n"; // New line if there is already content
                            }
                            $content .= $activity->getPermaURL();
                        }
                    }

                    if (!$content || ($activity->hasAttachments() && $activity->getCustomType() === 'video')) {
                        if ($content) {
                            $content .= ' ';
                        }
                        $content .= $activity->getURL();
                    }
                }

                $plainContent = $content;

                // By default, cc to the actors followers
                $cc = [
                    $actorUri . '/followers',
                ];

                $tag = $this->buildTag($plainContent);

                foreach ($tag as $t) {
                    // If a remote user, add to the cc
                    if (!$this->manager->isLocalUri($t['href'])) {
                        $cc[] = $t['href'];
                    }
                }

                $content = ContentParserBuilder::format($content);

                $json = [
                    'id' => $actorUri . '/entities/' . $activity->getUrn(),
                    'type' => 'Note',
                    'content' => $content,
                    'attributedTo' => $actorUri,
                    'to' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                    ],
                    'cc' => $cc,
                    'tag' => $tag,
                    'published' => date('c', $activity->getTimeCreated()),
                    'url' => $activity->getUrl(),
                    'source' => [
                        'content' => $plainContent,
                        'mediaType' => 'text/plain',
                    ],
                ];

                // Is this a quote post
                if ($activity->isQuotedPost()) {
                    if (!$activity->getRemind()) {
                        throw new NotFoundException("Could not find the quoted post for activity {$activity->getUrn()}");
                    }
                    $json['inReplyTo'] = $this->manager->getUriFromEntity($activity->getRemind());
                }

                // Any image attachments?
                if ($activity->hasAttachments() && $activity->getCustomType() === 'batch' && !$activity->isPayWall()) {
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
                $url = $this->manager->getSiteUrl() . 'newsfeed/' . $comment->getEntityGuid() . '?focusedCommentUrn=' . $comment->getUrn();

                if ($comment->getParentGuid()) {
                    $parentUrn = $comment->getParentUrn();
                } else {
                    $parentUrn = 'urn:entity:' . $comment->getEntityGuid();
                }

                /**
                 * Get the uri of what we are replying to
                 */
                $replyToUri = $this->manager->getUriFromUrn($parentUrn);

                $content = $plainContent = $comment->getBody();

                if (!$content || ($comment->hasAttachments() && $comment->getAttachments()['custom_type'] === 'video')) {
                    if ($content) {
                        $content .= ' ';
                    }
                    $content .= $url;
                    $plainContent = $content;
                }

                $content = ContentParserBuilder::format($content);

                // By default, cc to followers
                $cc = [
                    $actorUri . '/followers',
                ];

                $tag = $this->buildTag($plainContent);

                foreach ($tag as $t) {
                    // If a remote user, add to the cc
                    if (!$this->manager->isLocalUri($t['href'])) {
                        $cc[] = $t['href'];
                    }
                }

                $json = [
                    'id' => $actorUri . '/entities/' . $entity->getUrn(),
                    'type' => 'Note',
                    'content' => $content,
                    'attributedTo' => $actorUri,
                    'inReplyTo' => $replyToUri,
                    'to' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                    ],
                    'cc' => $cc,
                    'tag' => $tag,
                    'published' => date('c', (int) $comment->getTimeCreated()),
                    'url' => $url,
                    'source' => [
                        'content' => $plainContent,
                        'mediaType' => 'text/plain',
                    ],
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

        if (isset($json['tag'])) {
            $tag = [];
            foreach ($json['tag'] as $t) {
                if ($t['type'] !== 'Mention') {
                    continue;
                }
                $mention = new MentionType();
                $mention->href = $t['href'];
                $mention->name = $t['name'];
                $tag[] = $mention;
            }
            $object->tag = $tag;
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

        if (isset($json['source']) && is_array($json['source'])) {
            $object->source = new SourceType();
            $object->source->content = $json['source']['content'];
            $object->source->mediaType = $json['source']['mediaType'];
        }

        if (isset($json['quoteUri'])) {
            $object->quoteUri = $json['quoteUri'];
        }

        switch (get_class($object)) {
            case NoteType::class:
                $object->content = $json['content'] ?? '';
                break;
        }

        return $object;
    }

    private function buildTag(string $content): array
    {
        $tag = [];

        // Is this a mention?
        if ($mentions = ContentParserBuilder::getMentions($content)) {
            //
            foreach ($mentions as $mention) {
                $uri = $this->manager->getUriFromUsername($mention);

                if (!$uri) {
                    continue;
                }

                // Add all users to tge tags list
                $tag[] = [
                    'type' => 'Mention',
                    'href' => $uri,
                    'name' => $mention,
                ];
            }
        }

        return $tag;
    }
}
