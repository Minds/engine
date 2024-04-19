<?php

/**
 * Image entity
 */

namespace Minds\Entities;

use Imagick;
use ImagickException;
use InvalidParameterException;
use IOException;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Helpers;
use Minds\Helpers\StringLengthValidators\DescriptionLengthValidator;

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
 * @property string $auto_caption
 * @property bool $allow_comments
 */

class Image extends File implements MutatableEntityInterface, CommentableEntityInterface
{
    /**
     * @var array<string, bool|int|string|null>
     */
    public $attributes;
    public $guid;
    public $time_created;
    public $owner_guid;
    public $container_guid;
    public $title;
    public $description;
    private const THUMBNAILS_SIZES = [
        'xlarge' => [
            'height' => 1024,
            'width' => 1024,
            'isSquared' => false,
            'isUpscaled' => true
        ],
        'large' => [
            'height' => 600,
            'width' => 600,
            'isSquared' => false,
            'isUpscaled' => true
        ],
        'medium' => [
            'height' => 300,
            'width' => 300,
            'isSquared' => true,
            'isUpscaled' => true
        ],
        'small' => [
            'height' => 100,
            'width' => 100,
            'isSquared' => true,
            'isUpscaled' => true
        ],
    ];

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
        $this->attributes['license'] = null;
        $this->attributes['blurhash'] = null;
        $this->attributes['auto_caption'] = null;
        $this->attributes['allow_comments'] = true;
        $this->attributes['gif'] = false;
        $this->attributes['batch_guid'] = 0;
    }

    public function getUrl()
    {
        return Di::_()->get('Config')->get('site_url') . "media/$this->guid";
    }

    public function getIconUrl($size = 'large')
    {
        if ($this->time_created <= 1407542400) {
            $size = '';
        }
    
        $mediaManager = Di::_()->get('Media\Image\Manager');

        return $mediaManager->getPublicAssetUris($this, $size)[0];
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
     * @param string|null $filepath where to save the images
     * @return string xlarge image blob
     * @throws IOException
     * @throws ImagickException
     * @throws InvalidParameterException
     */
    public function createThumbnails(string $filepath = null): string
    {
        $sizes = ['xlarge', 'large', 'medium', 'small'];

        $master = $filepath ?: $this->getFilenameOnFilestore();
        $image = new Imagick($master);

        if ($this->gif) {
            return $this->createGifThumbnails($image);
        }

        return $this->createNonGifThumbnails($image);
    }

    /**
     * @param Imagick $image
     * @return string
     * @throws IOException
     * @throws ImagickException
     * @throws InvalidParameterException
     */
    private function createGifThumbnails(Imagick $image): string
    {
        if ($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
            $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }

        $image->autoOrient();

        $thumbnail = $image->getImagesBlob();

        $this->setFilename("image/$this->batch_guid/$this->guid/xlarge.jpg");
        $this->open('write');
        $this->write($thumbnail);
        $this->close();

        // TODO: reactivate when resizing for GIFs has been reactivated in Entities/Image.php
        // foreach (self::THUMBNAILS_SIZES as $size => $sizeProperties) {
        //     $currentImage = $this->resizeGif($image, $sizeProperties);
        //     $imageBlob = $currentImage->getImagesBlob();
        //
        //     if ($size == 'xlarge') {
        //         $thumbnail = $imageBlob;
        //     }
        //
        //     $this->setFilename("image/$this->batch_guid/$this->guid/$size.jpg");
        //     $this->open('write');
        //     $this->write($imageBlob);
        //     $this->close();
        // }

        return $thumbnail;
    }

    private function resizeGif(
        Imagick $image,
        array $sizeProperties
    ): Imagick {
        // If the GIF contains more than 6 images then return the GIF without resizing.
        if ($image->count() > 6) {
            return $image;
        }

        foreach ($image as $frame) {
            $frame->resizeImage(
                $sizeProperties['width'],
                $sizeProperties['height'],
                Imagick::FILTER_BOX,
                1,
                $sizeProperties['isUpscaled']
            );
        }

        return $image;
    }

    /**
     * Processes image thumbnails from a master image in reverse order from largest to smallest.
     * @param Imagick $image - image to process.
     * @return string - image blob of the thumbnail.
     * @throws ImagickException
     * @throws IOException
     * @throws InvalidParameterException
     */
    private function createNonGifThumbnails(Imagick $image): string
    {
        $thumbnail = '';
        $filepath = "image/$this->batch_guid/$this->guid";

        foreach (self::THUMBNAILS_SIZES as $size => $sizeProperties) {
            /** @var Core\Media\Imagick\Autorotate $autorotate */
            $autorotate = Core\Di\Di::_()->get('Media\Imagick\Autorotate');

            /** @var Core\Media\Imagick\Resize $resize */
            $resize = Core\Di\Di::_()->get('Media\Imagick\Resize');

            if ($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
                $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            }

            $autorotate->setImage($image);
            $image = $autorotate->autorotate();

            $resize->setImage($image)
                ->setUpscale($sizeProperties['isUpscaled'])
                ->setSquare($sizeProperties['isSquared'])
                ->setWidth($sizeProperties['width'])
                ->setHeight($sizeProperties['height'])
                ->resize();

            $imageBlob = $resize->getJpeg(90);

            if ($size == 'xlarge') {
                $thumbnail = $imageBlob;
            }

            // Save the thumbnail.
            $this->setFilename("$filepath/$size.jpg");
            $this->open('write');
            $this->write($imageBlob);
            $this->close();

            // replace image used for next iteration with current image.
            $image->removeImage();
            $image->readImageBlob($imageBlob);
        }

        // Set this instances filename back to xlarge as we want to save
        // this Image instance with the xlarge thumbnail as the filename.
        $this->setFilename("$filepath/xlarge.jpg");

        return $thumbnail;
    }

    /**
     * generate a blurHash from an image blob and sets the $this->blurhash key
     * @param string $thumbnail the image as string
     * @return string the blur hash
     * @throws ImagickException
     */
    public function generateBlurHash(string $thumbnail): string
    {
        $image = new Imagick();
        $image->readImageBlob($thumbnail);

        $resize = Core\Di\Di::_()->get('Media\Imagick\Resize');
        $resize->setImage($image)
            ->setUpscale(true)
            ->setSquare(false)
            ->setWidth(50)
            ->setHeight(50)
            ->resize();
        $imageBlob = $resize->getJpeg(90);

        /** @var Core\Media\Services\BlurHash $blurHashService */
        $blurHashService = Core\Di\Di::_()->get('Media\BlurHash');
        //
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
            'license',
            'blurhash',
            'paywall',
            'permaweb_id'
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
        $export['description'] = (new DescriptionLengthValidator())->validateMaxAndTrim($export['description']);

        $export['mature'] = $this->mature ?: $this->getFlag('mature');
        $export['rating'] = $this->getRating();
        $export['width'] = $this->width ?: 0;
        $export['height'] = $this->height ?: 0;
        $export['gif'] = (bool) $this->gif;
        $export['urn'] = $this->getUrn();
        $export['time_sent'] = $this->getTimeSent();
        $export['license'] = $this->license;
        $export['auto_caption'] = $this->getAutoCaption();

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
            'auto_caption' => "",
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
            'auto_caption',
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
            if (strpos($assets['media']['type'], '/gif') !== false) {
                $this->gif = 1;
            }

            $this->attributes['gif'] = isset($this->gif) && $this->gif ? 1 : 0;


            $thumbnail = $this->createThumbnails($assets['media']['file']);
            // NOTE: it's better if we use tiny, but we aren't resizing to tiny at the moment.
            // not sure if resizing to tiny and blurhash->encode('tiny' size) >> blurhash->encode('small' size)
            if ($thumbnail) {
                $this->generateBlurHash($thumbnail);
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
        $siteUrl = Di::_()->get('Config')->get('site_url');
        return [
            'batch',
            [[
                'src' => $siteUrl . 'fs/v1/thumbnail/' . $this->guid,
                'href' => $siteUrl . 'media/' . ($this->container_guid ? $this->container_guid . '/' : '') . $this->guid,
                'mature' => $this->getFlag('mature'),
                'nsfw' => $this->nsfw ?: [],
                'width' => $this->width ?? 0,
                'height' => $this->height ?? 0,
                'gif' => $this->gif,
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

    /**
     * Sets `license`
     * @param string $license
     * @return self
     */
    public function setLicense(string $license): self
    {
        $this->license = $license;
        return $this;
    }

    /**
     * Gets `license`
     * @return string|null
     */
    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function getAutoCaption(): ?string
    {
        return $this->auto_caption;
    }

    public function setAutoCaption(string $caption): self
    {
        $this->auto_caption = $caption;
        return $this;
    }

    /**
     * !!Support for images pre multi-image!!
     * Sets the flag for allowing comments on an entity
     * @param bool $allowComments
     */
    public function setAllowComments(bool $allowComments): self
    {
        $this->allow_comments = $allowComments;
        return $this;
    }

    /**
     * Gets the flag for allowing comments on an entity
     */
    public function getAllowComments(): bool
    {
        return (bool) $this->allow_comments;
    }
}
