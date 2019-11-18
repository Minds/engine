<?php
/**
 * Image Manager
 */
namespace Minds\Core\Media\Image;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Entities\Entity;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Core\Comments\Comment;
use Minds\Core\Security\SignedUri;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Zend\Diactoros\Uri;

class Manager
{
    /** @var Config $config */
    private $config;

    /** @var SignedUri $signedUri */
    private $signedUri;

    public function __construct($config = null, $signedUri = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->signedUri = $signedUri ?? new SignedUri;
    }

    /**
     * Return a public asset uri for entity type
     * @param Entity $entity
     * @param string $size
     * @return string
     */
    public function getPublicAssetUri($entity, $size = 'xlarge'): string
    {
        $uri = null;
        $asset_guid = null;
        switch (get_class($entity)) {
            case Activity::class:
                switch ($entity->get('custom_type')) {
                    case "batch":
                        $asset_guid = $entity->get('entity_guid');
                        break;
                    default:
                        $asset_guid = $entity->get('entity_guid');
                }
                break;
            case Image::class:
                $asset_guid = $entity->getGuid();
                break;
            case Video::class:
                $asset_guid = $entity->getGuid();
                break;
            case Comment::class:
                $asset_guid = $entity->getAttachments()['attachment_guid'];
                break;
        }

        $uri = $this->config->get('cdn_url') . 'fs/v1/thumbnail/' . $asset_guid . '/' . $size;

        if ($entity->access_id !== ACCESS_PUBLIC) {
            $uri = $this->signUri($uri);
        }

        return $uri;
    }

    /**
     * Sign a uri and return the uri with the signature attached
     * @param string $uri
     * @return string
     */
    private function signUri($uri, $pub = ""): string
    {
        $now = new \DateTime();
        $expires = $now->modify('midnight + 30 days')->getTimestamp();
        return $this->signedUri->sign($uri, $expires);
    }

    /**
     * Config signed uri
     * @param string $uri
     * @return string
     */
    public function confirmSignedUri($uri): bool
    {
        return $this->signedUri->confirm($uri);
    }
}
