<?php
namespace Minds\Core\Media\Image;

use Minds\Common\Access;
use Minds\Entities\User;
use Minds\Entities\Image as ImageEntity;
use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\Media\Assets\Image as ImageAssets;

class ProcessExternalImageService
{
    public function __construct(
        private readonly GuzzleClient $httpClient,
        private readonly ImageAssets $imageAssets,
    ) {
        
    }

    public function process(User $owner, string $url): ImageEntity
    {
        $image = new ImageEntity();
        $image->batch_guid = 0;
        $image->access_id = Access::UNLISTED;
        $image->owner_guid = $owner->getGuid();
        $image->container_guid = $owner->getGuid();

        // Save the image, so we get a guid
        $image->save();

        // Set the filename of our master image
        $image->filename = "/image/$image->batch_guid/$image->guid/master.jpg";

        /**
         * Save the image to a temporary file
         */
        $tmpFilename = "/tmp/{$image->guid}-master.jpg";
        $fp = fopen($tmpFilename, "w");
        $imageData = $this->fetchImageData($url);
        fwrite($fp, $imageData);
        fclose($fp);

        /**
         * Uploads the source file to S3 and handles resizing
         */
        $image->setAssets($this->imageAssets->upload([ 'file' => $tmpFilename ], $owner));

        unlink($tmpFilename);

        return $image;
    }

    /**
     * Performs the http GET request to grab the image
     */
    private function fetchImageData(string $url): string
    {
        $response = $this->httpClient->get($url);

        return $response->getBody()->getContents();
    }
}
