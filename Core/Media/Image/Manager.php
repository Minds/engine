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
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Zend\Diactoros\Uri;
use Minds\Core\Media\Video\CloudflareStreams;

class Manager
{
    /** @var Config */
    private $config;

    /** @var SignedUri */
    private $signedUri;

    /** @var CloudflareStreams\Manager  */
    private $cloudflareStreamsManager;

    public function __construct($config = null, $signedUri = null, $cloudflareStreamsManager = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->signedUri = $signedUri ?? new SignedUri;
        $this->cloudflareStreamsManager = $cloudflareStreamsManager ?? Di::_()->get('Media\Video\CloudflareStreams\Manager');
    }

    /**
     * Return a public asset uri for entity type.
     * ! THIS FUNCTION SHOULD ONLY BE CALLED IF WHEN CONFIDENT THE USER HAS PERMISSION TO VIEW THE ENTITY !
     * Typically an ACL check for permission will happen already on entity export.
     * @param Entity $entity
     * @param string $size
     * @return string[]
     */
    public function getPublicAssetUris($entity, $size = 'xlarge'): array
    {
        $uris = [];
        $assetGuids = [];
        $lastUpdated = null;
        switch (get_class($entity)) {
            case Activity::class:

                if ($entity->hasAttachments()) {
                    $assetGuids = array_map(function ($attachment) {
                        return $attachment['guid'];
                    }, $entity->attachments);
                    break;
                }

                switch ($entity->get('custom_type')) {
                    case "video":
                        $assetGuids[] = $entity->get('entity_guid');
                        // Cloudflare caches 302 redirect, so bust it each day
                        $entity->set('last_updated', strtotime('midnight'));
                        break;
                    case "batch":
                        $assetGuids[] = $entity->get('entity_guid');
                        break;
                    default:
                        $assetGuids[] = $entity->get('entity_guid');
                }
                $lastUpdated = $entity->get('last_updated');
                break;
            case Image::class:
                $assetGuids[] = $entity->getGuid();
                break;
            case Video::class:
                /** @var Video */
                $video = $entity;
                $assetGuids[] = $video->getGuid();
                $lastUpdated = $video->get('last_updated');
                if ($video->getTranscoder() === 'cloudflare' && !$video->thumbnail) {
                    return [ $this->cloudflareStreamsManager->getThumbnailUrl($video) ];
                }
                break;
            case Comment::class:
                $assetGuids[] = $entity->getAttachments()['attachment_guid'] ?? null;
                break;
        }

        foreach ($assetGuids as $assetGuid) {
            $path = 'fs/v1/thumbnail/' . $assetGuid . '/' . $size . '/' . $lastUpdated;
            $uris[] = $this->config->get('cdn_url') . $path;
        }

        if (
            $entity->access_id !== ACCESS_PUBLIC
            || $entity->owner_guid != $entity->container_guid
            || ($entity instanceof PaywallEntityInterface && $entity->isPayWall())
            || ($entity instanceof Activity && $entity->hasSiteMembership())
        ) {
            foreach ($uris as &$uri) {
                $uri = str_replace($this->config->get('cdn_url'), $this->config->get('site_url'), $uri);
            }

            $shouldSign = false;

            if ($entity instanceof PaywallEntityInterface && $entity->isPayWall()) {
                // Legacy paywall (wire thresholds)
                if ($entity->isPayWallUnlocked()) {
                    // We are signing because: this IS a paywalled post that we have permission to view as it is unlocked.
                    $shouldSign = true;
                }
            } elseif ($entity instanceof Activity && $entity->hasSiteMembership()) {
                // Site membership paywall
                $shouldSign = true;
            } else {
                // We are signing because; this is not a paywalled post, it is NOT public
                // and we DO have permission to view it. We know we have permission to view it
                // because prior to this function an ACL check will have been made on entity export
                // or a decision will have been made to manually override the check, for example for use in Jury.
                $shouldSign = true;
            }

            if ($shouldSign) {
                foreach ($uris as &$uri) {
                    $uri = $this->signUri($uri);
                }
            }

            // TODO: move this over to paywall manager via a hook (or something?)
            $loggedInUser = Session::getLoggedInUser();
            if ($entity instanceof PaywallEntityInterface && $entity->isPayWallUnlocked() || ($loggedInUser && $entity->owner_guid == $loggedInUser->getGuid())) {
                foreach ($uris as &$uri) {
                    $uri .= "&unlock_paywall=" . time();
                }
            }
        }

        return $uris;
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
