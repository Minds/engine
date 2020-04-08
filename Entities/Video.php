<?php
/**
 * A minds archive video entity
 *
 * Handles basic communication with cinemr
 */
namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Media\Services\Factory as ServiceFactory;
use Minds\Core\Di\Di;
use cinemr;
use Minds\Helpers;

class Video extends MindsObject
{
    private $cinemr;

    protected function initializeAttributes()
    {
        parent::initializeAttributes();

        $this->attributes['super_subtype'] = 'archive';
        $this->attributes['subtype'] = "video";
        $this->attributes['boost_rejection_reason'] = -1;
        $this->attributes['rating'] = 2;
        $this->attributes['time_sent'] = null;
    }


    public function __construct($guid = null)
    {
        parent::__construct($guid);
    }

    /**
     * Return the source url of the remote video
     * @param string $transcode
     * @return string
     */
    public function getSourceUrl($transcode = '720.mp4')
    {
        $mediaManager = Di::_()->get('Media\Video\Manager');
        return $mediaManager->getPublicAssetUri($this, $transcode);
    }

    /**
     * Uploads to remote
     *
     */

    public function upload($filepath)
    {
        // TODO: Confirm why this is still here
        $this->generateGuid();

        // Upload the source and start the transcoder pipeline
        $transcoderManager = Di::_()->get('Media\Video\Transcoder\Manager');
        $transcoderManager->uploadSource($this, $filepath)
            ->createTranscodes($this);

        // Legacy support
        $this->cinemr_guid = $this->getGuid();
    }

    public function getIconUrl($size = "medium")
    {
        // $domain = elgg_get_site_url();
        // global $CONFIG;
        // if (isset($CONFIG->cdn_url) && !$this->getFlag('paywall') && !$this->getWireThreshold()) {
        //     $domain = $CONFIG->cdn_url;
        // }

        // return $domain . 'api/v1/media/thumbnails/' . $this->guid . '/' . $this->time_updated;

        $mediaManager = Di::_()->get('Media\Image\Manager');
        return $mediaManager->getPublicAssetUri($this, 'medium');
    }

    public function getURL()
    {
        return elgg_get_site_url() . 'media/'.$this->guid;
    }

    protected function getIndexKeys($ia = false)
    {
        $indexes = [
            "object:video:network:$this->owner_guid"
        ];
        return array_merge(parent::getIndexKeys($ia), $indexes);
    }

    /**
     * Extend the default entity save function to update the remote service
     *
     */
    public function save($force = false)
    {
        $this->super_subtype = 'archive';
        parent::save((!$this->guid || $force));
        return $this->guid;
    }

    /**
     * Extend the default delete function to remove from the remote service
     */
    public function delete()
    {
        $result = parent::delete();

        return $result;
    }

    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), [
            'thumbnail',
            'cinemr_guid',
            'license',
            'monetized',
            'mature',
            'boost_rejection_reason',
            'time_sent',
        ]);
    }

    public function getAlbumChildrenGuids()
    {
        $db = new Core\Data\Call('entities_by_time');
        $row= $db->getRow("object:container:$this->container_guid", ['limit'=>100]);
        $guids = [];
        foreach ($row as $col => $val) {
            $guids[] = (string) $col;
        }
        return $guids;
    }

    /**
     * Extend exporting
     */
    public function export()
    {
        $export = parent::export();
        $export['thumbnail_src'] = $this->getIconUrl();
        $export['src'] = [
            '360.mp4' => $this->getSourceUrl('360.mp4'),
            '720.mp4' => $this->getSourceUrl('720.mp4')
        ];
        $export['play:count'] = Helpers\Counters::get($this->guid, 'plays');
        $export['thumbs:up:count'] = Helpers\Counters::get($this->guid, 'thumbs:up');
        $export['thumbs:down:count'] = Helpers\Counters::get($this->guid, 'thumbs:down');
        $export['description'] = (new Core\Security\XSS())->clean($this->description); //videos need to be able to export html.. sanitize soon!
        $export['rating'] = $this->getRating();
        $export['time_sent'] = $this->getTimeSent();

        if (!Helpers\Flags::shouldDiscloseStatus($this) && isset($export['flags']['spam'])) {
            unset($export['flags']['spam']);
        }

        if (!Helpers\Flags::shouldDiscloseStatus($this) && isset($export['flags']['deleted'])) {
            unset($export['flags']['deleted']);
        }

        $export['boost_rejection_reason'] = $this->getBoostRejectionReason() ?: -1;
        return $export;
    }

    /**
     * Generates a GUID, if there's none
     */
    public function generateGuid()
    {
        if (!$this->guid) {
            $this->guid = Core\Guid::build();
        }

        return $this->guid;
    }

    /**
     * Patches the entity
     */
    public function patch(array $data = [])
    {
        $this->generateGuid();

        $data = array_merge([
            'title' => null,
            'description' => null,
            'license' => null,
            'mature' => null,
            'nsfw' => null,
            'boost_rejection_reason' => null,
            'hidden' => null,
            'access_id' => null,
            'container_guid' => null,
            'rating' => 2, //open by default
            'time_sent' => time(),
            'full_hd' => false,
        ], $data);

        $allowed = [
            'title',
            'description',
            'license',
            'hidden',
            'access_id',
            'container_guid',
            'mature',
            'nsfw',
            'boost_rejection_reason',
            'rating',
            'time_sent',
            'full_hd',
        ];

        foreach ($allowed as $field) {
            if ($data[$field] === null) {
                continue;
            }

            if ($field == 'access_id') {
                $data[$field] = (int) $data[$field];
            } elseif (in_array($field, ['mature', 'full_hd'], true)) {
                $this->setFlag($field, !!$data[$field]);
                continue;
            }

            $this->$field = $data[$field];
        }

        return $this;
    }

    /**
     * Process the entity's assets
     */
    public function setAssets(array $assets)
    {
        $this->generateGuid();

        if (isset($assets['media'])) {
            $this->upload($assets['media']['file']);
        }

        if (isset($assets['thumbnail'])) {
            $this->thumbnail = $assets['thumbnail'];
        }
    }

    /**
     * Builds the newsfeed Activity parameters
     */
    public function getActivityParameters()
    {
        return [
            'video',
            [
                'thumbnail_src' => $this->getIconUrl(),
                'guid' => $this->guid,
                'mature' => $this->getFlag('mature'),
                'full_hd' => $this->getFlag('full_hd'),
                'license' => $this->license ?? '',
            ]
        ];
    }

    public function setBoostRejectionReason($reason)
    {
        $this->boost_rejection_reason = (int) $reason;
        return $this;
    }

    public function getBoostRejectionReason()
    {
        return $this->boost_rejection_reason;
    }

    public function getUrn()
    {
        return "urn:video:{$this->getGuid()}";
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
     * @return Image
     */
    public function setTimeSent($time_sent)
    {
        $this->time_sent = $time_sent;
        return $this;
    }

    /**
     * Set title
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get Title
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Return description
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description  ?: '';
    }

    /**
    * Set description
    *
    * @param string $description - description to be set.
    * @return Video
    */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set message (description)
     * @param string $description
     * @return self
     */
    public function setMessage($description): self
    {
        return $this->setDescription($description);
    }
}
