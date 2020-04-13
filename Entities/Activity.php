<?php
namespace Minds\Entities;

use Minds\Helpers;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Queue;
use Minds\Core\Analytics;

/**
 * Activity Entity
 */
class Activity extends Entity implements MutatableEntityInterface
{
    public $indexes = null;

    protected $dirtyIndexes = false;

    protected $hide_impressions = false;

    /** @var string */
    protected $videoPosterBase64Blob; // Never saves

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
            //	'node' => elgg_get_site_url()
        ]);
    }

    public function __construct($guid = null)
    {
        parent::__construct($guid);
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

        Queue\Client::build()->setQueue("FeedCleanup")
                            ->send([
                                "guid" => $this->guid,
                                "owner_guid" => $this->owner_guid,
                                "type" => "activity"
                            ]);

        Core\Events\Dispatcher::trigger('delete', 'activity', [ 'entity' => $this ]);

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

        if ($this->entity_guid && !$this->remind_object) {
            $export['thumbs:up:count'] = Helpers\Counters::get($this->entity_guid, 'thumbs:up');
            $export['thumbs:down:count'] = Helpers\Counters::get($this->entity_guid, 'thumbs:down');
        } elseif ($this->remind_object) {
            $export['remind_object']['nsfw'] = array_map(function ($reason) {
                return (int) $reason;
            }, $export['remind_object']['nsfw'] ?? []);
            if ($this->remind_object['entity_guid']) {
                $export['thumbs:up:count'] = Helpers\Counters::get($this->remind_object['entity_guid'], 'thumbs:up');
                $export['thumbs:down:count'] = Helpers\Counters::get($this->remind_object['entity_guid'], 'thumbs:down');
            } else {
                $export['thumbs:up:count'] = Helpers\Counters::get($this->remind_object['guid'], 'thumbs:up');
                $export['thumbs:down:count'] = Helpers\Counters::get($this->remind_object['guid'], 'thumbs:down');
            }
        } else {
            $export['thumbs:up:count'] = Helpers\Counters::get($this, 'thumbs:up');
            $export['thumbs:down:count'] = Helpers\Counters::get($this, 'thumbs:down');
        }
        $export['thumbs:up:user_guids'] = $export['thumbs:up:user_guids'] ? (array) array_values($export['thumbs:up:user_guids']) : [];

        $export['mature'] = (bool) $export['mature'];

        $export['comments_enabled'] = (bool) $export['comments_enabled'];
        $export['wire_totals'] = $this->getWireTotals();
        $export['wire_threshold'] = $this->getWireThreshold();
        $export['boost_rejection_reason'] = $this->getBoostRejectionReason() ?: -1;
        $export['rating'] = $this->getRating();
        $export['ephemeral'] = $this->getEphemeral();
        $export['ownerObj'] = $this->getOwnerObj();
        $export['time_sent'] = $this->getTimeSent();
        $export['license'] = $this->license;

        if ($this->hide_impressions) {
            $export['hide_impressions'] = $this->hide_impressions;
        }

        $export['thumbnails'] = $this->getThumbnails();

        switch ($this->custom_type) {
            case 'video':
                if ($this->custom_data['guid']) {
                    $export['play:count'] = Helpers\Counters::get($this->custom_data['guid'], 'plays');
                }
                break;
            case 'batch':
                // fix old images src
                if (is_array($export['custom_data']) && strpos($export['custom_data'][0]['src'], '/wall/attachment') !== false) {
                    $export['custom_data'][0]['src'] = Core\Config::_()->cdn_url . 'fs/v1/thumbnail/' . $this->entity_guid;
                    $this->custom_data[0]['src'] = $export['custom_data'][0]['src'];
                }
                // go directly to cdn
                $mediaManager = Di::_()->get('Media\Image\Manager');
                $export['custom_data'][0]['src'] = $export['thumbnails']['xlarge'];
                break;
        }

        if (Helpers\Flags::shouldDiscloseStatus($this)) {
            $export['spam'] = (bool) $this->getSpam();
            $export['deleted'] = (bool) $this->getDeleted();
        }

        $export = array_merge($export, \Minds\Core\Events\Dispatcher::trigger('export:extender', 'activity', ['entity'=>$this], []));


        return $export;
    }

    /**
     * Returns a friendly URL
     * @return string
     */
    public function getURL()
    {
        return elgg_get_site_url() . 'newsfeed/'.$this->guid;
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
    public function setMessage($message)
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
     * Set the reminded object
     * @param array $array - the exported array
     * @return $this
     */
    public function setRemind($array)
    {
        $this->remind_object = $array;
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
        return (boolean) $this->edited;
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
     * Sets if there is a paywall or not
     * @param mixed $value
     */
    public function setPayWall($value)
    {
        $this->paywall = (bool) $value;
        return $this;
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
     * Checks if there is a paywall for this post
     * @return boolean
     */
    public function isPayWall()
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
        if ($this->remind_object) {
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

    /**
     * Gets wire threshold
     * @return mixed
     */
    public function getWireThreshold()
    {
        return $this->wire_threshold;
    }

    /**
     * Sets wire threshold
     * @param $wire_threshold
     * @return $this
     */
    public function setWireThreshold($wire_threshold)
    {
        $this->wire_threshold = $wire_threshold;
        return $this;
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
                break;
            case 'batch':
                $mediaManager = Di::_()->get('Media\Image\Manager');
                $sizes = [ 'xlarge', 'large' ];
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
    public function getUrn()
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
}
