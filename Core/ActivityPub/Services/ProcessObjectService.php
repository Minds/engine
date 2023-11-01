<?php
namespace Minds\Core\ActivityPub\Services;

use Minds\Common\Access;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Core\ActivityPub\Helpers\ContentParserBuilder;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Object\DocumentType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Feeds\Activity\RemindIntent;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Image\ProcessExternalImageService;
use Minds\Core\Security\ACL;
use Minds\Core\Subscriptions;
use Minds\Core\Votes\Manager as VotesManager;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use Minds\Helpers\Url;

class ProcessObjectService
{
    private ObjectType $object;

    public function __construct(
        protected Manager $manager,
        protected ProcessActorService $processActorService,
        protected EmitActivityService $emitActivityService,
        protected ObjectFactory $objectFactory,
        protected ACL $acl,
        protected ActivityManager $activityManager,
        protected Subscriptions\Manager $subscriptionsManager,
        private readonly VotesManager $votesManager,
        protected ProcessExternalImageService $processExternalImageService,
        protected MetascraperService $metascraperService,
        protected Config $config,
        protected Logger $logger,
        protected Save $save,
    ) {
        
    }

    public function withObject(ObjectType $object): ProcessObjectService
    {
        $instance = clone $this;
        $instance->object = $object;
        return $instance;
    }

    public function process(bool $requireMinSubscribers = true): void
    {
        $logPrefix = "{$this->object->id}: ";

        $owner = $this->processActorService
            ->withActorUri($this->object->attributedTo)
            ->process(update: false);

        if (!$owner) {
            // The owner could not be found. The owner must be present
            return;
        }

        /**
         * Process the Note as a Minds Activity
         */
        if ($this->object instanceof NoteType) {

            /**
             * The owner and have at least one subscriber for their posts to be ingested
             * If this is a reply, then we don't check min subscribers
             */
            if (
                $requireMinSubscribers
                && !isset($this->object->inReplyTo)
                && $this->subscriptionsManager->setSubscriber($owner)->getSubscribersCount() === 0
            ) {
                $this->logger->info("$logPrefix Can not pull in post for {$owner->getGuid()}: No subscribers");
                return;
            }

            // If activity has been previously imported, then
            $existing = $this->manager->getEntityFromUri($this->object->id);
            if ($existing) {
                $this->logger->info("$logPrefix The post already exists");
                // No need to import as we already have it
                // TODO: support update?
                return;
            }

            // Does the post have any attachments?

            // Is this a reply?
            // Do we have the post that is being replied to?
            if (isset($this->object->inReplyTo)) {
                $inReplyToEntity = $this->manager->getEntityFromUri($this->object->inReplyTo);
                if (!$inReplyToEntity) {
                    // Should we fetch a new one?
                    // For now we will not
                    $this->logger->info("$logPrefix The post that is being replied to could not be found. It may not yet exist on Minds.");
                    return;
                }

                // Ignore ACL as we need to be able to act on another users behalf
                $ia = $this->acl->setIgnore(true);

                // We will always treat Fediverse replies as comments

                $comment = new Comment();
                
                if ($inReplyToEntity instanceof Comment) {
                    $comment->setEntityGuid($inReplyToEntity->getEntityGuid());
                    $comment->setParentGuidL1(0);
                    $comment->setParentGuidL2(0);

                    $parentGuids = explode(':', $inReplyToEntity->getChildPath());
                    $comment->setParentGuidL1($parentGuids[0]);
                    $comment->setParentGuidL2($parentGuids[1]);
                } else {
                    $comment->setEntityGuid($inReplyToEntity->getGuid());
                    $comment->setParentGuidL1(0);
                    $comment->setParentGuidL2(0);
                }

                $comment->setBody(ContentParserBuilder::sanitize($this->getContent()));
                $comment->setOwnerGuid($owner->getGuid());
                $comment->setTimeCreated(time());
                $comment->setSource(FederatedEntitySourcesEnum::ACTIVITY_PUB);

                if (isset($this->object->url)) {
                    $comment->setCanonicalUrl($this->object->url);
                }

                // Are there any urls? If so lets build a rich embed from the first url we find
                if ($urls = ContentParserBuilder::getUrls($comment->getBody() ?: '')) {
                    $url = $urls[0];
                    try {
                        $richEmbed = $this->metascraperService->scrape($url);
                
                        $comment->setAttachment('title', $richEmbed['meta']['title']);
                        $comment->setAttachment('blurb', $richEmbed['meta']['description']);
                        $comment->setAttachment('perma_url', Url::normalize($url));
                        $comment->setAttachment('thumbnail_src', $richEmbed['links']['thumbnail'][0]['href']);
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                    } catch (\Exception $e) {
                        $this->logger->error($logPrefix . $e->getMessage());
                    }
                }

                /**
                 * If any images, then fetch them
                 */
                $images = $this->processImages(
                    owner: $owner,
                    max: 1
                );
                
                if (count($images)) {
                    $siteUrl = $this->config->get('site_url');
                    $comment->setAttachment('custom_type', 'image');
                    $comment->setAttachment('custom_data', [
                        'guid' => (string) $images[0]->guid,
                        'container_guid' => (string) $images[0]->container_guid,
                        'src'=> $siteUrl . 'fs/v1/thumbnail/' . $images[0]->guid,
                        'href'=> $siteUrl . 'media/' . $images[0]->container_guid . '/' . $images[0]->guid,
                        'mature' => false,
                        'width' => $images[0]->width,
                        'height' => $images[0]->height,
                    ]);
                    $comment->setAttachment('attachment_guid', $images[0]->guid);

                    // Fix the access_id on the image
                    $this->patchImages($comment, $images);
                }

                $commentsManager = new \Minds\Core\Comments\Manager();
                $commentsManager->add($comment);
                
                // Save the comment
                $this->manager->addUri(
                    uri: $this->object->id,
                    guid: (int) $comment->getGuid(),
                    urn: $comment->getUrn(),
                );

                // Reset ACL state
                $this->acl->setIgnore($ia);

                return;
            }

            // Ignore ACL as we need to be able to act on another users behalf
            $ia = $this->acl->setIgnore(true);

            /**
             * Create the Activity
             */
            $entity = $this->prepareActivity($owner);

            if (isset($this->object->url)) {
                $entity->setCanonicalUrl($this->object->url);
            } else {
                $entity->setCanonicalUrl($this->object->id);
            }

            $entity->setMessage($this->getContent());

            // If any images, then fetch them
            $images = $this->processImages(
                owner: $owner,
                max: 4
            );

            // Add the images as attachments
            $entity->setAttachments($images);

            // Are there any urls? If so lets build a rich embed from the first url we find
            if (($urls = ContentParserBuilder::getUrls($entity->getMessage() ?: '')) && !$entity->hasAttachments()) {
                $url = $urls[0];
                try {
                    $richEmbed = $this->metascraperService->scrape($url);
                    $entity
                        ->setTitle($richEmbed['meta']['title'])
                        ->setBlurb($richEmbed['meta']['description'])
                        ->setURL($url)
                        ->setThumbnail($richEmbed['links']['thumbnail'][0]['href']);
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                } catch (\Exception $e) {
                    $this->logger->error($logPrefix . $e->getMessage());
                }
            }

            // Is this a quote post
            if (isset($this->object->quoteUri)) {
                // Fetch the orignal activity
                $quoteObject = $this->objectFactory->fromUri($this->object->quoteUri);

                if (!$this->manager->isLocalUri($this->object->quoteUri)) {
                    // Pull in the remote content
                    $this->withObject($quoteObject)->process(requireMinSubscribers: false);
                }

                $quotePost = $this->manager->getEntityFromUri($this->object->quoteUri);

                $remind = new RemindIntent();
                $remind->setGuid($quotePost->getGuid());
                $remind->setOwnerGuid($quotePost->getOwnerGuid());
                $remind->setQuotedPost(true);

                $entity->setRemind($remind);
            }
        
            // Save the activity
            $this->activityManager->add($entity);

            // Patch image access
            if (count($images)) {
                $this->patchImages($entity, $images);
            }

            // Reset ACL state
            $this->acl->setIgnore($ia);

            // Save reference so we don't import this again
            $this->manager->addUri(
                uri: $this->object->id,
                guid: (int) $entity->getGuid(),
                urn: $entity->getUrn(),
            );
        }

        return;
    }

    /**
     * Helper function to build an Activity entity with the correct attributes
     */
    public function prepareActivity(User $owner): Activity
    {
        $entity = new Activity();

        $entity->setAccessId(Access::PUBLIC);
        $entity->setSource(FederatedEntitySourcesEnum::ACTIVITY_PUB);
    
        // Requires cleanup (see TwitterSync and Nostr)
        $entity->container_guid = $owner->guid;
        $entity->owner_guid = $owner->guid;
        $entity->ownerObj = $owner->export();

        return $entity;
    }

    /**
     * If we have a source object, we will try and read from that
     */
    private function getContent(): string
    {
        if (isset($this->object->source) && $this->object->source->mediaType === 'text/plain') {
            $content = $this->object->source->content;
        } else {
            $content = $this->object->content;
        }
        return ContentParserBuilder::sanitize($content);
    }

    /**
     * @return Image[]
     */
    private function processImages(User $owner, int $max = 4): array
    {
        $images = [];

        if (isset($this->object->attachment) && count($this->object->attachment)) {
            foreach ($this->object->attachment as $attachment) {
                if (count($images) >= $max) {
                    break;
                }
                if (!$attachment instanceof DocumentType) {
                    continue;
                }
                if (strpos($attachment->mediaType, 'image/', 0) === false) {
                    continue; // Not a valid image
                }
                $images[] = $this->processExternalImageService->process($owner, $attachment->url);
            }
        }

        return $images;
    }
    
    /**
     * When we create the images, we are not aware of the GUID
     * After the Activity is saved, and we have a GUID, we can then patch the Images
     * with the correct access_id and container_guid
     */
    private function patchImages(EntityInterface $entity, array $images): void
    {
        foreach ($images as $image) {
            if ($entity instanceof Activity) {
                $image->setAccessId($entity->getGuid());
                $image->setContainerGUID($entity->getGuid());
            } elseif ($entity instanceof Comment) {
                $image->setAccessId($entity->getAccessId());
                $image->setContainerGUID($entity->getAccessId());
            } else {
                return;
            }

            // Save the image with our new parent
            $this->save
                ->setEntity($image)
                ->withMutatedAttributes([
                    'access_id',
                    'container_guid',
                ])
                ->save();
        }
    }
}
