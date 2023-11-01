<?php

namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Analytics;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Activity\RemindIntent;
use Minds\Core\Queue;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Core\Wire\Paywall\PaywallEntityTrait;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Helpers;

/**
 * Activity Entity
 * @property array $ownerObj
 * @property User $owner
 * @property int $boost_rejection_reason
 * @property array $wire_threshold
 * @property array $remind_object
 * @property int $comments_enabled
 * @property int $paywall
 * @property int $edited
 * @property int $deleted
 * @property int $spam
 * @property int $pending
 * @property int $ephemeral
 * @property string $entity_guid
 * @property int $mature
 * @property string $to_guid
 * @property int $boosted
 * @property int $boosted_onchain
 * @property int $p2p_boosted
 * @property string $title
 * @property string $message
 * @property string $perma_url
 * @property string $blurb
 * @property array $custom_data
 * @property string $custom_type
 * @property string $thumbnail_src
 * @property string $boosted_guid
 * @property string $urn
 * @property int $time_sent
 * @property string $license
 * @property string $blurhash
 * @property array $attachments
 * @property array $supermind
 * @property string $auto_caption
 * @property array $inferred_tags
 * @property string $source
 * @property string $canonical_url
 */
class Activity extends Entity implements MutatableEntityInterface, PaywallEntityInterface, CommentableEntityInterface, FederatedEntityInterface
{
    use PaywallEntityTrait;

    /** @var array */
    public $indexes = null;

    /** @var bool */
    protected $dirtyIndexes = false;

    /** @var bool */
    protected $hide_impressions = false;

    /** @var string */
    protected $videoPosterBase64Blob; // Never saves

    /** @var Core\EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Core\Feeds\Activity\Manager */
    protected $activityManager;

    /**
     * Initialize entity attributes
     * @return null
     */
    public function initializeAttributes()
    {
        parent::initializeAttributes();
        $this->attributes = array_merge($this->attributes, [
            'type' => 'activity',
            'owner_guid' => elgg_get_logged_in_user_guid(),
            'access_id' => 2, //private,
            'mature' => false,
            'spam' => false,
            'deleted' => false,
            'paywall' => false,
            'edited' => false,
            'comments_enabled' => true,
            'wire_threshold' => null,
            'pending' => false,
            'rating' => 2, //open by default
            'ephemeral' => false,
            'time_sent' => null,
            'license' => '',
            'blurhash' => null,
            'attachments' => null,
            'supermind' => null,
            'auto_caption' => null,
            'inferred_tags' => [],
            'source' => FederatedEntitySourcesEnum::LOCAL->value,
            'canonical_url' => null,
        ]);
    }

    public function __construct($guid = null, $cache = null, $entitiesBuilder = null, $activityManager = null)
    {
        parent::__construct($guid);
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->activityManager = $activityManager ?? Di::_()->get('Feeds\Activity\Manager');
    }

    /**
     * Returns an array of indexes into which this entity is stored
     *
     * @param  bool $ia - ignore access
     * @return array
     */
    protected function getIndexKeys($ia = false)
    {
        if ($this->indexes) {
            return $this->indexes;
        }

        $indexes = [
            $this->type
        ];

        $ownerGuid = $this->getOwnerGuid();

        array_push($indexes, "$this->type:user:$ownerGuid");
        array_push($indexes, "$this->type:network:$ownerGuid");


        if ($this->to_guid == $ownerGuid) {
            array_push($indexes, "$this->type:user:own:$ownerGuid");
        }

        /**
         * @todo make it only post to a group if we are in a group
         */
        if (!$this->getPending()) {
            array_push($indexes, "$this->type:container:$this->container_guid");
        }

        /**
         * Make a link from entity to this activity post
         */
        if ($this->entity_guid) {
            array_push($indexes, "$this->type:entitylink:$this->entity_guid");
        }

        return $indexes;
    }

    /**
     * Returns an array of which Entity attributes are exportable
     * @return array
     */
    public function getExportableValues()
    {
        return array_merge(
            parent::getExportableValues(),
            [
                'title',
                'blurb',
                'perma_url',
                'message',
                'ownerObj',
                'containerObj',
                'thumbnail_src',
                'remind_object',
                'entity_guid',
                'featured',
                'featured_guid',
                'custom_type',
                'custom_data',
                'thumbs:up:count',
                'thumbs:up:user_guids',
                'thumbs:down:count',
                'thumbs:down:user_guids',
                'p2p_boosted',
                'mature',
                'monetized',
                //'paywall',
                'edited',
                'wire_totals',
                'wire_threshold',
                'boost_rejection_reason',
                'pending',
                'rating',
                'ephemeral',
                'hide_impressions',
                'pinned',
                'time_sent',
                'permaweb_id',
                'blurhash',
                'supermind',
                'auto_caption',
                'inferred_tags',
                'canonical_url',
                'source',
            ]
        );
    }

    /**
     * Exports the activity onto an array
     * @return array
     */
    public function export()
    {
        $export = parent::export();

        if ($this->isRemind() && $remind = $this->getRemind(true)) {
            // If this is a remind (not a quoted post), then we export the remind and not this post
            $export = $remind->export();
            $export['subtype'] = 'remind';

            // TODO: when we support collapsing of reminds, add the other ownerObj's

            $remindOwner = $this->entitiesBuilder->single($this->getOwnerGuid(), [ 'cacheTtl' => 259200 ]); // Move to export extender

            $export['remind_users'] = [$remindOwner->export()];
            $export['urn'] = $this->getUrn();
            return $export;
        } else {
            if ($this->isQuotedPost() && $remind = $this->getRemind(true)) {
                // Only one quoted post can be included. Present a link if on 3rd layer down
                if ($remind->isQuotedPost()) {
                    $url = $remind->getRemindUrl();
                    $remind->setMessage($remind->getMessage() . ' ' . $url);
                    $remind->setRemind(null); // Remove the remind so we don't get recursion
                }

                $export['remind_object'] = $remind->export();
            }

            if ($this->entity_guid) {
                $export['entity_guid'] = (string) $this->entity_guid;
            }

            if ($this->urn) {
                $export['urn'] = $this->urn;
            }

            if ($this->boosted_guid) {
                $export['boosted'] = (bool) $this->boosted;
                $export['boosted_guid'] = (string) $this->boosted_guid;
                $export['boosted_onchain'] = $this->boosted_onchain;
            }

            $export['impressions'] = $this->getImpressions();
            $export['reminds'] = $this->getRemindCount();

            // Thumbs:up exports moved to Core/Votes/Events

            $export['mature'] = (bool) $export['mature'];

            $export['allow_comments'] = $this->getAllowComments();
            // $export['wire_totals'] = $this->getWireTotals();
            $export['wire_threshold'] = $this->getWireThreshold();
            $export['boost_rejection_reason'] = $this->getBoostRejectionReason() ?: -1;
            $export['rating'] = $this->getRating();
            $export['ephemeral'] = $this->getEphemeral();
            // $export['ownerObj'] = $this->getOwnerObj();
            $export['time_sent'] = $this->getTimeSent();
            $export['license'] = $this->license;
            $export['blurhash'] = $this->blurhash;

            if ($this->hide_impressions) {
                $export['hide_impressions'] = $this->hide_impressions;
            }

            if (Helpers\Flags::shouldDiscloseStatus($this)) {
                $export['spam'] = (bool) $this->getSpam();
                $export['deleted'] = (bool) $this->getDeleted();
            }
        }

        // If remind deleted or remind invalid, remove from export
        if ($export['remind_object'] && !$export['remind_object']['type']) {
            $export['remind_object'] = null;
            $export['remind_deleted'] = true;
            if ($this->remind_object['guid']) {
                $export['message'] .= ' ' . $this->getRemindUrl();
            }
        }

        /**
         * Multi media attachment export logic
         * Does not cover legacy entity_guid export logic.
         * Convert attachments to custom data
         */
        if ($this->hasAttachments()) {
            $export['custom_type'] = $this->getCustomType();
            $export['custom_data'] = $this->getCustomData();

            if ($export['custom_type'] === 'video') {
                $export['entity_guid'] = (string) $this->getGuid(); // mobile expects this
                $export['thumbnail_src'] = $export['custom_data']['src']; // discovery/plus expects this
            }
        }

        $export = array_merge($export, \Minds\Core\Events\Dispatcher::trigger('export:extender', 'activity', ['entity' => $this], []));


        return $export;
    }

    /**
     * Returns a friendly URL
     * @return string
     */
    public function getURL()
    {
        return Di::_()->get('Config')->get('site_url') . 'newsfeed/' . $this->guid;
    }

    /**
     * Set the message
     * @param string $message
     * @return $this
     */
    public function setMessage($message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the message
     * @return string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Sets the title
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the title
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the blurb
     * @param string $blurb
     * @return $this
     */
    public function setBlurb($blurb)
    {
        $this->blurb = $blurb;
        return $this;
    }

    /**
     * Gets the blurb
     * @return string
     */
    public function getBlurb(): string
    {
        return $this->blurb;
    }

    /**
     * Sets the url
     * @param string $url
     * @return $this
     */
    public function setURL($url)
    {
        $this->perma_url = $url;
        return $this;
    }

    /**
     * Sets the thumbnail
     * @param string $src
     * @return $this
     */
    public function setThumbnail($src)
    {
        $this->thumbnail_src = $src;
        return $this;
    }

    /**
     * Sets the thumbnail
     * @return string
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail_src;
    }

    /**
     * Sets the license
     * @param string $license
     * @return Activity
     */
    public function setLicense(string $license): Activity
    {
        $this->license = $license;
        return $this;
    }

    /**
     * Gets the license
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * Sets the owner
     * @param mixed $owner
     * @return $this
     */
    public function setOwner($owner)
    {
        if (is_numeric($owner)) {
            $owner = new \Minds\Entities\User($owner);
            $owner = $owner->export();
        }

        $this->owner = $owner;

        return $this;
    }

    /**
     * Sets the entity GUID for this activity
     * @param string|int $entityGuid
     * @return Activity
     */
    public function setEntityGuid($entityGuid = ''): Activity
    {
        $this->entity_guid = $entityGuid ?: '';
        return $this;
    }

    /**
     * Gets the entity GUID from this activity. Can be null.
     * @return string|int|null
     */
    public function getEntityGuid()
    {
        return $this->entity_guid ?: null;
    }

    /**
     * Returns an entity object if it exists
     * !! Can be ineffecient as it will cause anothor database call !!
     * @return mixed
     */
    public function getEntity()
    {
        if (!$this->getEntityGuid()) {
            return null;
        }
        return $this->entitiesBuilder->single($this->getEntityGuid());
    }

    /**
     * Set from a local minds object
     * @return $this
     */
    public function setFromEntity($entity)
    {
        $this->entity_guid = $entity->guid;
        $this->ownerObj = $entity->ownerObj;
        return $this;
    }

    /**
     * Set a custom, arbitrary set. For example a custom video view, or maybe a set of images. I envisage
     * certain service could extend this.
     * @param string|null $type
     * @param array|null $data
     * @return $this
     */
    public function setCustom($type, $data = [])
    {
        $this->custom_type = $type ?: '';
        $this->custom_data = $data ?: [];
        return $this;
    }

    /**
     * Get the custom data (deprecated, use getCustomType() and getCustomData())
     * @return array
     */
    public function getCustom(): array
    {
        return [
            $this->custom_type ?: null,
            $this->custom_data
        ];
    }

    /**
     * Set the to_guid
     * @param int $guid
     * @return $this
     */
    public function setToGuid($guid)
    {
        $this->to_guid = $guid;
        return $this;
    }

    /**
     * Sets the maturity flag for this activity
     * @param mixed $value
     */
    public function setMature($value)
    {
        $this->mature = (bool) $value;
        return $this;
    }

    /**
     * Gets the maturity flag
     * @return boolean
     */
    public function getMature()
    {
        return (bool) $this->mature;
    }

    /**
     * Sets the ephemeral flag for this activity
     * @param bool $value
     * @return Activity
     */
    public function setEphemeral($value)
    {
        $this->ephemeral = (bool) $value;
        return $this;
    }

    /**
     * Gets the ephemeral flag
     * @return boolean
     */
    public function getEphemeral()
    {
        return (bool) $this->ephemeral;
    }

    /**
     * Sets the pending (container queue) flag for this activity
     * @param mixed $value
     * @return $this
     */
    public function setPending($value)
    {
        $this->pending = (bool) $value;
        return $this;
    }

    /**
     * Gets the pending (container queue) flag
     * @return boolean
     */
    public function getPending()
    {
        return (bool) $this->pending;
    }

    /**
     * Sets the spam flag for this activity
     * @param mixed $value
     */
    public function setSpam($value)
    {
        $this->spam = (bool) $value;
        return $this;
    }

    /**
     * Gets the spam flag
     * @return boolean
     */
    public function getSpam()
    {
        return (bool) $this->spam;
    }

    /**
     * Sets the deleted flag for this activity
     * @param mixed $value
     */
    public function setDeleted($value)
    {
        $this->deleted = (bool) $value;
        $this->dirtyIndexes = true;
        return $this;
    }

    /**
     * Gets the spam flag
     * @return boolean
     */
    public function getDeleted()
    {
        return (bool) $this->deleted;
    }

    /**
     * Sets if activity has been edited
     * @param boolean
     */
    public function setEdited($value)
    {
        $this->edited = $value;
        return $this;
    }

    /**
     * Gets if activity has been edited
     * @return boolean
     */
    public function getEdited()
    {
        return (bool) $this->edited;
    }

    /**
     * Sets the timestamp for this activity
     * @param mixed $value
     */
    public function setTimeCreated($value)
    {
        $this->time_created = (int) $value;
        return $this;
    }

    /**
     * Gets the timestamp
     * @return int
     */
    public function getTimeCreated()
    {
        return $this->time_created;
    }

    /**
     * Return if there is a paywall or not
     * @return bool
     */
    public function getPayWall(): bool
    {
        return (bool) $this->paywall;
    }

    /**
     * Return the count for this entity
     * @return int
     */
    public function getImpressions()
    {
        $app = Analytics\App::_()
            ->setMetric('impression')
            ->setKey($this->guid);
        return $app->total();
    }

    /**
     * Return the count of reminds
     * @return int
     */
    public function getRemindCount()
    {
        if ($this->isRemind()) {
            // Only return count of parent if remind, not quoted post
            return \Minds\Helpers\Counters::get($this->remind_object['guid'], 'remind');
        }

        return \Minds\Helpers\Counters::get($this, 'remind');
    }

    /**
     * Returns the sum of every wire that's been made to this entity
     */
    public function getWireTotals()
    {
        $guid = $this->guid;

        if ($this->remind_object) {
            $guid = $this->remind_object['guid'];
        }

        $totals = [];
        $totals['tokens'] = Core\Wire\Counter::getSumByEntity($guid, 'tokens');
        return $totals;
    }

    public function getBoostRejectionReason()
    {
        return $this->boost_rejection_reason;
    }

    public function setBoostRejectionReason($reason)
    {
        $this->boost_rejection_reason = (int) $reason;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHideImpressions()
    {
        return (bool) $this->hide_impressions;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHideImpressions($value)
    {
        $this->hide_impressions = (bool) $value;
        return $this;
    }

    /**
     * Return thumbnails array to be used with export
     * TODO: Possibly deprecate now we have ->attachment support
     * @return array
     */
    public function getThumbnails(): array
    {
        $thumbnails = [];
        $customType = $this->custom_type;

        if ($this->hasAttachments()) {
            $customType = 'multiImage';
        }

        switch ($customType) {
            case 'video':
                $mediaManager = Di::_()->get('Media\Image\Manager');
                $thumbnails['xlarge'] = $mediaManager->getPublicAssetUris($this, 'xlarge')[0];
                break;
            case 'batch':
                $mediaManager = Di::_()->get('Media\Image\Manager');
                $sizes = ['xlarge', 'large'];
                foreach ($sizes as $size) {
                    $thumbnails[$size] = $mediaManager->getPublicAssetUris($this, $size)[0];
                }
                break;
            case 'multiImage':
                $mediaManager = Di::_()->get('Media\Image\Manager');
                $sizes = ['xlarge', 'large'];
                foreach ($sizes as $size) {
                    $thumbnails[$size] = $mediaManager->getPublicAssetUris($this, $size)[0];
                }
                break;
        }
        return $thumbnails;
    }

    /**
     * Return a preferred urn
     * @return string
     */
    public function getUrn(): string
    {
        return "urn:activity:{$this->getGuid()}";
    }

    /**
     * Return time_sent
     * @return int
     */
    public function getTimeSent()
    {
        return $this->time_sent;
    }

    /**
     * Set time_sent
     * @return Activity
     */
    public function setTimeSent($time_sent)
    {
        $this->time_sent = $time_sent;
        return $this;
    }

    /**
     * Sets base64 video blob but never saves
     * @param string $blob
     * @return self
     */
    public function setVideoPosterBase64Blob(string $blob): self
    {
        $this->videoPosterBase64Blob = $blob;
        return $this;
    }

    /**
     * Returns base64 video poster if provided (never saved to db)
     * @return string
     */
    public function getVideoPosterBase64Blob(): ?string
    {
        return $this->videoPosterBase64Blob;
    }

    /**
     * Sets `access_id`
     * @param mixed $access_id
     * @return Activity
     */
    public function setAccessId($access_id): Activity
    {
        $this->access_id = $access_id;
        return $this;
    }

    /**
     * Gets `access_id`
     * @return mixed
     */
    public function getAccessId(): string
    {
        return $this->access_id;
    }

    //

    public function getTags(): array
    {
        $tags = parent::getTags() ?: [];
        $messageTags = Helpers\Text::getHashtags($this->message);
        return array_unique(array_merge($tags, $messageTags));
    }


    //


    /**
     * Set the reminded object
     * @param RemindIntent $remindIntent
     * @return $this
     */
    public function setRemind(?RemindIntent $remindIntent): self
    {
        $this->remind_object = $remindIntent ? $remindIntent->export() : null;
        return $this;
    }

    /**
     * @param bool $clone - if true it will return a new instance
     * @return Activity
     */
    public function getRemind($clone = false): ?Activity
    {
        if (!$this->remind_object || !$this->remind_object['guid']) {
            return null;
        }

        $entity = $this->entitiesBuilder->single($this->remind_object['guid']);
        if (!$entity) {
            return null;
        }

        if (!$entity instanceof Activity) {
            $guid = $entity->getGuid();
            $entity = $this->activityManager->createFromEntity($entity);
            $entity->guid = $guid; // Not ideal hack here
        }

        if ($clone === true) {
            return clone $entity;
        } else {
            return $entity;
        }
    }

    /**
     * @return bool
     */
    public function isRemind(): bool
    {
        return !$this->message
            && is_array($this->remind_object)
            && $this->remind_object['guid']
            && !($this->remind_object['quoted_post'] ?? true);
    }

    /**
     * A quoted post is like a remind, but it usually has a message
     * Reminds prior to November 2020 are always quoted posts
     * @return bool
     */
    public function isQuotedPost(): bool
    {
        return is_array($this->remind_object)
            && $this->remind_object['guid']
            && !$this->isRemind();
    }

    /**
     * Return remind link
     * @return string
     */
    private function getRemindUrl(): string
    {
        return Di::_()->get('Config')->get('site_url') . 'newsfeed/' . $this->remind_object['guid'];
    }

    /**
     * Build denormalized views of attachments
     * @param Video[]|Image[] $attachmentEntities
     */
    public function setAttachments(array $attachmentEntities)
    {
        $attachments = [];
        foreach ($attachmentEntities as $attachmentEntity) {
            $attachment = [
                'guid' => $attachmentEntity->getGuid(),
                'type' => $attachmentEntity->getSubtype(),
                'width' => $attachmentEntity->width,
                'height' => $attachmentEntity->height,
                'blurhash' => $attachmentEntity->blurhash,
                'gif' => $attachmentEntity->gif
            ];

            $attachments[] = $attachment;
        }
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * Returns true/false if the activity has any attachments
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return $this->attachments && count($this->attachments) > 0;
    }

    /**
     * Get Supermind details.
     * @return array|null supermind details.
     */
    public function getSupermind(): ?array
    {
        return $this->supermind;
    }

    /**
     * @param array $supermindDetails
     * @return $this
     */
    public function setSupermind(array $supermindDetails): self
    {
        $this->supermind = $supermindDetails;
        return $this;
    }

    public function getAutoCaption(): ?string
    {
        return $this->auto_caption;
    }

    public function setAutoCaption(string $autoCaption): self
    {
        $this->auto_caption = $autoCaption;
        return $this;
    }

    public function getInferredTags(): ?array
    {
        return $this->inferred_tags;
    }

    public function setInferredTags(array $inferredTags): self
    {
        $this->inferred_tags = $inferredTags;
        return $this;
    }

    /**
     * Will return isPortrait logic for posts
     * @return bool
     */
    public function isPortrait(): bool
    {
        $isPortrait = false;

        // Video

        if ($this->custom_type === 'video' && is_array($this->custom_data)) {
            $isPortrait = $this->custom_data['height'] > $this->custom_data['width'];
        }

        // Image (legacy entity_guid)
        if (
            in_array($this->custom_type, ['image', 'batch'], true) &&
            is_array($this->custom_data) &&
            is_array($this->custom_data[0])
        ) {
            $isPortrait = $this->custom_data[0]['height'] > $this->custom_data[0]['width'];
        }

        // Multi media attachments
        if (
            $this->hasAttachments() &&
            count($this->attachments) === 1 // you can only have isPortrait if single image post
        ) {
            $isPortrait = $this->attachments[0]['height'] > $this->attachments[0]['width'];
        }

        return $isPortrait;
    }

    /**
     * Returns the custom type of activity
     * @return string
     */
    public function getCustomType(): ?string
    {
        if ($this->hasAttachments()) {
            return $this->attachments[0]['type'] === 'video' ? 'video' : 'batch';
        }

        return $this->custom_type;
    }

    /**
     * Returns the custom data
     * @return array
     */
    public function getCustomData(): ?array
    {
        if ($this->hasAttachments()) {
            // hydrate the src urls (includes signing)
            $mediaManager = Di::_()->get('Media\Image\Manager');
            $imageUrls = $mediaManager->getPublicAssetUris($this, 'xlarge');
            $customData = $this->attachments;

            foreach ($customData as $k => $v) {
                $customData[$k]['src'] = $imageUrls[$k];
            }

            // currently does not support array
            if ($this->getCustomType() === 'video') {
                return $customData[0];
            } else {
                return $customData;
            }
        }

        return $this->custom_data;
    }

    /**
     * Reconstructs our dependencies when we unserialized
     */
    public function __wakeup()
    {
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $this->activityManager = Di::_()->get('Feeds\Activity\Manager');
    }

    /**
     * Removes entitiesBuilder and activityManager from object
     * when serializing
     */
    public function __sleep()
    {
        $diff = array_diff(
            array_merge(array_keys(
                get_object_vars($this)
            ), ['attributes']),
            [
                'entitiesBuilder',
                'activityManager',
            ]
        );
        return $diff;
    }

    /**
     * Sets the flag for allowing comments on an entity
     * @param bool $allowComments
     */
    public function setAllowComments(bool $allowComments): self
    {
        $this->comments_enabled = $allowComments;
        return $this;
    }

    /**
     * Gets the flag for allowing comments on an entity
     */
    public function getAllowComments(): bool
    {
        return (bool) $this->comments_enabled;
    }

    /**
     * @inheritDoc
     */
    public function setSource(FederatedEntitySourcesEnum $source): FederatedEntityInterface
    {
        $this->source = $source->value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSource(): ?FederatedEntitySourcesEnum
    {
        return FederatedEntitySourcesEnum::from($this->source ?: 'local');
    }

    /**
     * @inheritDoc
     */
    public function setCanonicalUrl(string $canonicalUrl): FederatedEntityInterface
    {
        $this->canonical_url = $canonicalUrl;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCanonicalUrl(): ?string
    {
        return $this->canonical_url;
    }
}
