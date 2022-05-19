<?php

namespace Minds\Core\Notifications\Push\System\Builders;

use Minds\Core\Blogs\Blog;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\Entity;
use Minds\Entities\Image;

/**
 * Notification builder for daily digest notifications. Used to get a
 * CustomPushNotification populated with information for daily digest.
 */
class DailyDigestPushNotificationBuilder implements EntityPushNotificationBuilderInterface
{
    /**
     * Constructor
     * @param ?Config $config - config.
     */
    public function __construct(private ?Config $config = null)
    {
        $this->config ??= Di::_()->get('Config');
    }
    
    /**
     * Build a daily digest CustomPushNotification.
     * @param Entity|Blog|Image $entity - entity to build for.
     * @return CustomPushNotification - populated CustomPushNotification.
     */
    public function build(Entity|Blog|Image $entity): CustomPushNotification
    {
        if ($entity->getType() === 'activity' && $entity->getEntity()) {
            $entity = $entity->getEntity();
        }
        return (new CustomPushNotification())
            ->setTitle($this->buildTitle($entity))
            ->setBody($this->buildBody($entity))
            ->setUri($this->buildUri($entity))
            ->setMedia($this->buildMediaUrl($entity));
    }

    /**
     * Builds title.
     * @param Entity|Blog|Image $entity - entity to build title for.
     * @return ?string - title for push notification.
     */
    protected function buildTitle(Entity|Blog|Image $entity): string
    {
        $usernameString = $this->getOwnerUsernameString($entity);

        switch ($entity->getType()) {
            case 'object':
                switch ($entity->getSubtype()) {
                    case 'blog':
                        return $usernameString . ' posted a blog';
                    case 'image':
                        if (!$entity->getTitle() && !$entity->getDescription()) {
                            return ' '; // can't be blank, so insert a space char
                        }
                        return $usernameString . ' posted an image';
                    case 'video':
                        if (!$entity->getTitle() && !$entity->getDescription()) {
                            return ' '; // can't be blank, so insert a space char
                        }
                        return $usernameString . ' posted a video';
                }
            default:
                return $usernameString . ' posted';
        }
    }

    /**
     * Builds body.
     * @param Entity|Blog|Image $entity - entity to build body for.
     * @return ?string - body for push notification.
     */
    protected function buildBody(Entity|Blog|Image $entity): ?string
    {
        switch ($entity->getType()) {
            case 'activity':
                return $entity->getMessage();
            case 'object':
                $usernameString = $this->getOwnerUsernameString($entity);

                switch ($entity->getSubtype()) {
                    case 'blog':
                        return $entity->getTitle();
                    case 'image':
                        if (!$entity->getTitle() && !$entity->getDescription()) {
                            return $usernameString . ' posted an image';
                        }
                        return $entity->getTitle() ? $entity->getTitle() : $entity->getDescription();
                    case 'video':
                        if (!$entity->getTitle() && !$entity->getDescription()) {
                            return $usernameString . ' posted a video';
                        }
                        return $entity->getTitle() ? $entity->getTitle() : $entity->getDescription();
                }
            default:
                return '';
        }
    }

    /**
     * Builds URI to serve as the push notification link.
     * @param Entity|Blog|Image $entity - entity to build URI for.
     * @return ?string - URI for push notification.
     */
    protected function buildUri(Entity|Blog|Image $entity): ?string
    {
        switch ($entity->getType()) {
            case 'object':
                switch ($entity->getSubtype()) {
                    case 'blog':
                        return $entity->getPermaUrl();
                    default:
                        return $this->config->get('site_url') . 'newsfeed/' . $entity->getGuid();
                }
            default:
                return $this->config->get('site_url') . 'newsfeed/' . $entity->getGuid();
        }
    }

    /**
     * Builds media URL.
     * @param Entity|Blog|Image $entity - entity to build media URL for.
     * @return ?string - media URL for push notification.
     */
    protected function buildMediaUrl(Entity|Blog|Image $entity): ?string
    {
        switch ($entity->getType()) {
            case 'object':
                return $entity->getIconUrl('large');
            default:
                return '';
        }
    }

    /**
     * Gets username string.
     * @param Entity|Blog|Image $entity - entity to get username string for.
     * @return string - username string (@username).
     */
    protected function getOwnerUsernameString(Entity|Blog|Image $entity): string
    {
        return '@' . $entity->getOwnerEntity()->getUsername();
    }
}
