<?php

namespace Minds\Entities;

use Minds\Helpers;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Queue;
use Minds\Core\Analytics;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Core\Wire\Paywall\PaywallEntityTrait;
use Minds\Core\Feeds\Activity\RemindIntent;

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
 * @property string $permaweb_id
 * @property string $blurhash
 */
class Activity extends Entity implements MutatableEntityInterface, PaywallEntityInterface
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
            'boost_rejection_reason' => -1,
            'pending' => false,
            'rating' => 2, //open by default
            'ephemeral' => false,
            'time_sent' => null,
            'license' => '',
            'permaweb_id' => '',
            'blurhash' => null,
            //	'node' => elgg_get_site_url()
        ]);
    }

    public function __construct($guid = null, $cache = null, $entitiesBuilder = null, $activityManager = null)
    {
        parent::__construct($guid);
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->activityManager = $activityManager ?? Di::_()->get('Feeds\Activity\Manager');
        if ($cache) {
        }
    }

    /**
     * Saves the activity
     * @param  bool  $index - save to index
     * @return mixed        - the GUID
     */
    public function save($index = true)
    {
        if ($this->getEphemeral()) {
            throw new \Exception('Cannot save an ephemeral activity');
        }

        //cache owner_guid for brief
        if (!$this->ownerObj && $owner = $this->getOwnerEntity(false)) {
            $this->ownerObj = $owner->export();
        }

        if ($this->getDeleted()) {
            $index = false;

            if ($this->dirtyIndexes) {
                $indexes = $this->getIndexKeys(true);

                $db = new Core\Data\Call('entities_by_time');
                foreach ($indexes as $idx) {
                    $db->removeAttributes($idx, [$this->guid]);
                }

                Queue\Client::build()->setQueue("FeedCleanup")
                    ->send([
                        "guid" => $this->guid,
                        "owner_guid" => $this->owner_guid,
                        "type" => "activity"
                    ]);
            }
        } else {
            if ($this->dirtyIndexes) {
                // Re-add to indexes, force as true
                $index = true;
            }
        }

        $guid = parent::save($index);

        if ($this->isPayWall()) {
            (new Core\Payments\Plans\PaywallReview())
                ->setEntityGuid($guid)
                ->add();
        }

        return $guid;
    }

    /**
     * Deletes the activity entity and indexes
     * @return bool
     */
    public function delete()
    {
        if ($this->getEphemeral()) {
            throw new \Exception('Cannot save an ephemeral activity');
        }

        if ($this->p2p_boosted) {
            return false;
        }

        $indexes = $this->getIndexKeys(true);
        $db = new Core\Data\Call('entities');
        $res = $db->removeRow($this->guid);

        $db = new Core\Data\Call('entities_by_time');
        foreach ($indexes as $index) {
            $db->removeAttributes($index, [$this->guid]);
        }

        (new Core\Translation\Storage())->purge($this->guid);

        Core\Events\Dispatcher::trigger('entities-ops', 'delete', [
            'entityUrn' => $this->getUrn()
        ]);

        Queue\Client::build()->setQueue("FeedCleanup")
            ->send([
                "guid" => $this->guid,
                "owner_guid" => $this->owner_guid,
                "type" => "activity"
            ]);

        Core\Events\Dispatcher::trigger('delete', 'activity', ['entity' => $this]);

        return true;
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

        $owner = $this->getOwnerEntity();

        array_push($indexes, "$this->type:user:$owner->guid");
        array_push($indexes, "$this->type:network:$owner->guid");


        if ($this->to_guid == $owner->guid) {
            array_push($indexes, "$this->type:user:own:$owner->guid");
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
                'paywall',
                'edited',
                'comments_enabled',
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
                'blurhash'
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
            $export['remind_users'] = [$this->ownerObj];
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

            $export['comments_enabled'] = (bool) $export['comments_enabled'];
            // $export['wire_totals'] = $this->getWireTotals();
            $export['wire_threshold'] = $this->getWireThreshold();
            $export['boost_rejection_reason'] = $this->getBoostRejectionReason() ?: -1;
            $export['rating'] = $this->getRating();
            $export['ephemeral'] = $this->getEphemeral();
            $export['ownerObj'] = $this->getOwnerObj();
            $export['time_sent'] = $this->getTimeSent();
            $export['license'] = $this->license;
            $export['blurhash'] = $this->blurhash;

            if ($this->hide_impressions) {
                $export['hide_impressions'] = $this->hide_impressions;
            }

            $export['permaweb_id'] = $this->getPermawebId();

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

        $export = array_merge($export, \Minds\Core\Events\Dispatcher::trigger('export:extender', 'activity', ['entity' => $this], []));


        return $export;
    }

    /**
     * Returns a friendly URL
     * @return string
     */
    public function getURL()
    {
        return elgg_get_site_url() . 'newsfeed/' . $this->guid;
    }

    /**
     * Returns the owner entity
     * @return mixed
     */
    public function getOwnerEntity($brief = false)
    {
        return parent::getOwnerEntity(true);
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
     * Get the custom data
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
     * Sets if comments are enabled
     * @param boolean
     */
    public function enableComments()
    {
        $this->comments_enabled = true;
        return $this;
    }
    public function disableComments()
    {
        $this->comments_enabled = false;
        return $this;
    }

    /**
     * Gets if comments are enabled
     * @return boolean
     */
    public function canComment($user_guid = 0 /* ignored */)
    {
        return (bool) $this->comments_enabled;
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
     * @return boolean
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

    public function getOwnerObj()
    {
        if (!$this->ownerObj && $this->owner_guid) {
            $user = new User($this->owner_guid);
            $this->ownerObj = $user->export();
        }

        return $this->ownerObj;
    }

    /**
     * Return thumbnails array to be used with export
     * @return array
     */
    public function getThumbnails(): array
    {
        $thumbnails = [];
        switch ($this->custom_type) {
            case 'video':
                $mediaManager = Di::_()->get('Media\Image\Manager');
                $thumbnails['xlarge'] = $mediaManager->getPublicAssetUri($this, 'xlarge');
                break;
            case 'batch':
                $mediaManager = Di::_()->get('Media\Image\Manager');
                $sizes = ['xlarge', 'large'];
                foreach ($sizes as $size) {
                    $thumbnails[$size] = $mediaManager->getPublicAssetUri($this, $size);
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

    /**
     * Sets `permaweb_id`
     * @param string $permaweb_id
     * @return Activity
     */
    public function setPermawebId(string $permaweb_id): Activity
    {
        $this->permaweb_id = $permaweb_id;
        return $this;
    }

    /**
     * Gets `permaweb_id`
     * @return string
     */
    public function getPermawebId(): string
    {
        return $this->permaweb_id;
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
}
