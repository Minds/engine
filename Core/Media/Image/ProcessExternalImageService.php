<?php
namespace Minds\Core\Media\Image;

use Minds\Common\Access;
use Minds\Entities\User;
use Minds\Entities\Image as ImageEntity;
use GuzzleHttp\Client as GuzzleClient;

class ProcessExternalImageService
{
    public function __construct(
        private readonly GuzzleClient $httpClient
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
         * Uploads the source file to S3
         */
        $file = new \ElggFile(); //only using for legacy reasons
        $file->owner_guid = $owner->getGuid();
        $file->container_guid = $owner->getGuid();
        $file->setFilename("/image/$image->batch_guid/$image->guid/master.jpg");
        $file->open('write');
        $file->write($imageData);
        $file->close();

        /**
         * Create sizes
         */
        //$loc = $image->getFilenameOnFilestore();
        $image->createThumbnails($tmpFilename);
        $image->save();
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
