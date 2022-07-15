<?php
namespace Minds\Core\Feeds\TwitterSync;

use GuzzleHttp;
use GuzzleHttp\Client;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Feeds\Activity\Delegates\AttachmentDelegate;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\User;

/**
 * ImageExtractor extracts photos from twitter URLs and can be used to attach them to a given activity.
 */
class ImageExtractor
{
    // Regexes for URLs we permit to be extracted as images.
    const IMAGE_URL_WHITELIST_REGEX = [
        '/^https:\/\/pbs\.twimg\.com\/media\/[\w\d-]+\.\w+$/'
    ];

    /**
     * ImageExtractor constructor.
     * @param GuzzleHttp\Client $httpClient
     * @param Logger $logger
     * @param AttachmentDelegate $attachmentDelegate
     * @param Save $saveAction
     */
    public function __construct(
        protected ?Client $httpClient = null,
        protected ?Logger $logger = null,
        protected ?AttachmentDelegate $attachmentDelegate = null,
        protected ?Save $saveAction = null
    ) {
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->attachmentDelegate = $attachmentDelegate ?? new AttachmentDelegate();
        $this->saveAction = $saveAction ?? new Save();
    }

    /**
     * Extracts a photo from Twitter, uploads them to our site and attaches the new Image Entity
     * to a given Activity.
     * @param MediaData $mediaData - media data object containing ONLY photo media data.
     * @param Activity $activity - activity to attach the image to.
     * @return Activity - activity with attached image.
     */
    public function extractAndUploadToActivity(MediaData $mediaData, Activity $activity): Activity
    {
        try {
            $owner = $activity->getOwnerEntity();

            $entityGuid = $this->extractAndUpload($mediaData, $owner);

            if (!$entityGuid) {
                return $activity;
            }

            $activity->setEntityGuid($entityGuid);

            $this->attachmentDelegate
                ->setActor($owner)
                ->onCreate($activity, $entityGuid);

            return $activity;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return $activity;
        }
    }

    /**
     * Extracts from twitter and uploads to our server.
     * @param MediaData $mediaData - the media data object of the photo.
     * @param User $owner - the owner user.
     * @return string - entity guid after uploading.
     */
    protected function extractAndUpload(MediaData $mediaData, User $owner): string
    {
        $imageStream = $this->extract($mediaData);

        if (!$imageStream) {
            return '';
        }

        return $this->upload($mediaData, $imageStream, $owner);
    }

    /**
     * Extracts a photo from Twitter.
     * @param MediaData $mediaData - the media data object of the photo.
     * @return string stream of the image.
     */
    protected function extract(MediaData $mediaData): string
    {
        $imageUrl = $mediaData->getUrl();

        if (!$this->isWhiteListedUrl($imageUrl)) {
            $this->logger->error("Attempted to parse not whitelisted URL from Twitter: $imageUrl");
            return '';
        }

        $response = $this->httpClient->request('GET', $imageUrl, [
            'stream' => true
        ]);

        $body = $response->getBody()->getContents();

        return $body;
    }

    /**
     * Upload an image stream to our server.
     * @param MediaData $mediaData - the media data object of the photo.
     * @param string $imageStream - stream of image as string.
     * @param User $user - user to upload for.
     * @return string - entity_guid once uploaded.
     */
    protected function upload(MediaData $mediaData, string $imageStream, User $user): string
    {
        $image = new Image();
        $image->ownerObj = $user;
        $image->owner_guid = $user->getGuid();
        $image->batch_guid = 0;
        $image->access_id = 0;
        $image->width = $mediaData->getWidth() ?? null;
        $image->height = $mediaData->getHeight() ?? null;

        $guid = $this->saveAction->setEntity($image)->save(true);

        $image->filename = "/image/$image->batch_guid/$image->guid/master.jpg";
        $fp = fopen("/tmp/{$image->guid}-master.jpg", "w");
        fwrite($fp, $imageStream);
        fclose($fp);

        $image->createThumbnails("/tmp/{$image->guid}-master.jpg");
        unlink("/tmp/{$image->guid}-master.jpg");

        return $guid;
    }

    /**
     * Ensures a URL we are checking is whitelisted.
     * @param string $url - url to check
     * @return boolean - true if URL is whitelisted.
     */
    protected function isWhitelistedUrl(string $url): bool
    {
        $allowedUrl = false;
        foreach (self::IMAGE_URL_WHITELIST_REGEX as $whitelistedUrlRegex) {
            if (preg_match($whitelistedUrlRegex, $url)) {
                $allowedUrl = true;
            }
        }
        return $allowedUrl;
    }
}
