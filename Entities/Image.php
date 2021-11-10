<?php

/**
 * Image entity
 */

namespace Minds\Entities;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Helpers;

/**
 * @property string $super_subtype
 * @property string $filename
 * @property int $batch_guid
 * @property int $width
 * @property int $height
 * @property int $gif
 * @property int $mature
 * @property string $license
 * @property int $boost_rejection_reason
 * @property int $time_sent
 * @property string $blurhash
 * @property array $nsfw
 * @property string $permaweb_id
 * @property int $rating
 */

class Image extends File
{
    protected function initializeAttributes()
    {
        parent::initializeAttributes();

        $this->attributes['super_subtype'] = 'archive';
        $this->attributes['subtype'] = "image";
        $this->attributes['boost_rejection_reason'] = -1;
        $this->attributes['rating'] = 2;
        $this->attributes['width'] = 0;
        $this->attributes['height'] = 0;
        $this->attributes['time_sent'] = null;
        $this->attributes['blurhash'] = null;
    }

    public function getUrl()
    {
        return elgg_get_site_url() . "media/$this->guid";
    }

    public function getIconUrl($size = 'large')
    {
        global $CONFIG; //@todo remove globals!
        if ($this->time_created <= 1407542400) {
            $size = '';
        }

        // if ($this->access_id != 2) {
        //     $base_url = \elgg_get_site_url();
        // }
        $mediaManager = Di::_()->get('Media\Image\Manager');

        return $mediaManager->getPublicAssetUri($this, $size);
    }

    protected function getIndexKeys($ia = false)
    {
        $indexes = [
            "object:image:network:$this->owner_guid"
        ];
        return array_merge(parent::getIndexKeys($ia), $indexes);
    }

    /**
     * Extend the default entity save function to update the remote service
     *
     */
    public function save($index = true)
    {
        $this->super_subtype = 'archive';

        parent::save($index);

        return $this->guid;
    }

    /**
     * Extend the default delete function to remove from the remote service
     */
    public function delete()
    {
        return parent::delete();

        //remove from the filestore
    }

    /**
     * Return the folder in which this image is stored
     */
    public function getFilePath()
    {
        return str_replace($this->getFilename(), '', $this->getFilenameOnFilestore());
    }


    public function upload($file)
    {
        $this->generateGuid();

        if (!$this->filename) {
            $dir = $this->getFilenameOnFilestore() . "/image/$this->batch_guid/$this->guid";
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        if (!$file['tmp_name']) {
            throw new \Exception("Upload failed. The image may be too large");
        }

        $this->filename = "image/$this->batch_guid/$this->guid/" . $file['name'];

        $filename = $this->getFilenameOnFilestore();
        $result = move_uploaded_file($file['tmp_name'], $filename);

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * Creates thumbnails for the image, saves to fs, and returns the image blobgs
     * @param string[] $sizes thumbnail sizes
     * @param string $filepath where to save the iamges
     * @return string[] image blobs
     */
    public function createThumbnails($sizes = ['small', 'medium', 'large', 'xlarge'], $filepath = null): array
    {
        if (!$sizes) {
            $sizes = ['small', 'medium', 'large', 'xlarge'];
        }
        $master = $filepath ?: $this->getFilenameOnFilestore();
        $thumbnails = [];
        foreach ($sizes as $size) {
            switch ($size) {
                case 'tiny':
                    $h = 25;
                    $w = 25;
                    $s = true;
                    $u = true;
                    break;
                case 'small':
                    $h = 100;
                    $w = 100;
                    $s = true;
                    $u = true;
                    break;
                case 'medium':
                    $h = 300;
                    $w = 300;
                    $s = true;
                    $u = true;
                    break;
                case 'large':
                    $h = 600;
                    $w = 600;
                    $s = false;
                    $u = true;
                    break;
                case 'xlarge':
                    $h = 1024;
                    $w = 1024;
                    $s = false;
                    $u = true;
                    break;
                default:
                    continue 2;
            }

            /** @var Core\Media\Imagick\Autorotate $autorotate */
            $autorotate = Core\Di\Di::_()->get('Media\Imagick\Autorotate');

            /** @var Core\Media\Imagick\Resize $resize */
            $resize = Core\Di\Di::_()->get('Media\Imagick\Resize');

            $image = new \Imagick($master);

            if ($image->getImageColorspace() == \Imagick::COLORSPACE_CMYK) {
                $image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            }

            $autorotate->setImage($image);
            $image = $autorotate->autorotate();

            $resize->setImage($image)
                ->setUpscale($u)
                ->setSquare($s)
                ->setWidth($w)
                ->setHeight($h)
                ->resize();

            $imageBlob = $resize->getJpeg(90);
            $thumbnails[$size] = $imageBlob;

            $this->setFilename("image/$this->batch_guid/$this->guid/$size.jpg");
            $this->open('write');
            $this->write($imageBlob);
            $this->close();
        }

        return $thumbnails;
    }

    /**
     * generate a blurHash from an image blob and sets the $this->blurhash key
     * @param $imageBlob the image as string
     * @return string the blur hash
     */
    public function generateBlurHash(string $imageBlob): string
    {
        /** @var Core\Media\Services\BlurHash $blurHashService */
        $blurHashService = Core\Di\Di::_()->get('Media\BlurHash');
        $blurHash = $blurHashService->getHash($imageBlob);
        $this->blurhash = $blurHash;

        return $this->blurhash;
    }

    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), [
            'thumbnail',
            'cinemr_guid',
            'license',
            'mature',
            'boost_rejection_reason',
            'rating',
            'width',
            'height',
            'gif',
            'time_sent',
            'blurhash',
            'paywall',
            'permaweb_id',
        ]);
    }

    public function getAlbumChildrenGuids()
    {
        $db = new Core\Data\Call('entities_by_time');
        $row = $db->getRow("object:container:$this->container_guid", ['limit' => 100]);
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
        $export['thumbnail_src'] = $this->getIconUrl('xlarge');
        $export['thumbnail'] = $export['thumbnail_src'];
        $export['description'] = $this->description; //videos need to be able to export html.. sanitize soon!
        $export['mature'] = $this->mature ?: $this->getFlag('mature');
        $export['rating'] = $this->getRating();
        $export['width'] = $this->width ?: 0;
        $export['height'] = $this->height ?: 0;
        $export['gif'] = (bool) $this->gif;
        $export['urn'] = $this->getUrn();
        $export['time_sent'] = $this->getTimeSent();

        $export['permaweb_id'] = $this->getPermawebId();
        $export['blurhash'] = $this->blurhash;

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
            'batch_guid' => null,
            'access_id' => null,
            'container_guid' => null,
            'rating' => 2, //open by default
            'time_sent' => time(),
            'blurhash' => null,
        ], $data);

        $allowed = [
            'title',
            'description',
            'license',
            'hidden',
            'batch_guid',
            'access_id',
            'container_guid',
            'mature',
            'nsfw',
            'boost_rejection_reason',
            'rating',
            'time_sent',
            'blurhash',
        ];

        foreach ($allowed as $field) {
            if ($data[$field] === null) {
                continue;
            }

            if ($field == 'access_id') {
                $data[$field] = (int) $data[$field];
            } elseif ($field == 'mature') {
                $this->setFlag('mature', !!$data['mature']);
            } elseif ($field == 'nsfw') {
                $this->setNsfw($data['nsfw']);
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

        if (isset($assets['filename'])) {
            $this->filename = $assets['filename'];
        }

        if (isset($assets['media'])) {
            $thumbnails = $this->createThumbnails(null, $assets['media']['file']);
            // NOTE: it's better if we use tiny, but we aren't resizing to tiny at the moment.
            // not sure if resizing to tiny and blurhash->encode('tiny' size) >> blurhash->encode('small' size)
            $this->generateBlurHash($thumbnails['small']);

            if (strpos($assets['media']['type'], '/gif') !== false) {
                $this->gif = true;
            }
        }

        $this->width = $assets['width'] ?: 0;
        $this->height = $assets['height'] ?: 0;

        if (isset($assets['container_guid'])) {
            $this->container_guid = $assets['container_guid'];
        }
    }

    /**
     * Builds the newsfeed Activity parameters
     */
    public function getActivityParameters()
    {
        return [
            'batch',
            [[
                'src' => \elgg_get_site_url() . 'fs/v1/thumbnail/' . $this->guid,
                'href' => \elgg_get_site_url() . 'media/' . ($this->container_guid ? $this->container_guid . '/' : '') . $this->guid,
                'mature' => $this->getFlag('mature'),
                'nsfw' => $this->nsfw ?: [],
                'width' => $this->width ?? 0,
                'height' => $this->height ?? 0,
                'gif' => (bool) ($this->gif ?? false),
                'license' => $this->license ?? '',
            ]]
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

    public function getUrn(): string
    {
        return "urn:image:{$this->guid}";
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
     * @return Image
     */
    public function setDescription($description): Image
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

    /**
     * Sets `permaweb_id`
     * @param string $permaweb_id
     * @return Activity
     */
    public function setPermawebId(string $permaweb_id): Image
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
}
