<?php

namespace Minds\Core\Notifications\Push\System\Builders;

use Minds\Core\Blogs\Blog;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\Entity;
use Minds\Entities\Image;
use Minds\Entities\Video;

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
    public function build(Entity|Blog|Image|Video $entity): CustomPushNotification
    {
        if ($entity->getType() === 'activity' && $entity->getEntity()) {
            $entity = $entity->getEntity();
        }
        return (new CustomPushNotification())
            ->setTitle($this->buildTitle($entity), 0, 65)
            ->setBody($this->buildBody($entity))
            ->setUri($this->buildUri($entity))
            ->setMedia($this->buildMediaUrl($entity));
    }

    /**
     * Builds title.
     * @param Entity|Blog|Image $entity - entity to build title for.
     * @return ?string - title for push notification.
     */
    protected function buildTitle(Entity|Blog|Image|Video $entity): string
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
                // no break
            default:
                return $usernameString . ' posted';
        }
    }

    /**
     * Builds body.
     * @param Entity|Blog|Image|Video $entity - entity to build body for.
     * @return ?string - body for push notification.
     */
    protected function buildBody(Entity|Blog|Image|Video $entity): ?string
    {
        switch ($entity->getType()) {
            case 'activity':
                $body = $entity->getMessage();
                break;
            case 'object':
                $usernameString = $this->getOwnerUsernameString($entity);

                switch ($entity->getSubtype()) {
                    case 'blog':
                        $body = $entity->getTitle();
                        break;
                    case 'image':
                        if (!$entity->getTitle() && !$entity->getDescription()) {
                            $body = $usernameString . ' posted an image';
                            break;
                        }
                        $body = $entity->getTitle() ? $entity->getTitle() : $entity->getDescription();
                        break;
                    case 'video':
                        if (!$entity->getTitle() && !$entity->getDescription()) {
                            $body = $usernameString . ' posted a video';
                            break;
                        }
                        $body = $entity->getTitle() ? $entity->getTitle() : $entity->getDescription();
                        break;
                }
                break;
            default:
                return '';
        }

        if (mb_strlen($body) > 175) {
            $body = mb_substr($body, 0, 170).'...';
        }

        return $body;
    }

    /**
     * Builds URI to serve as the push notification link.
     * @param Entity|Blog|Image|Video $entity - entity to build URI for.
     * @return ?string - URI for push notification.
     */
    protected function buildUri(Entity|Blog|Image|Video $entity): ?string
    {
        switch ($entity->getType()) {
            case 'object':
                switch ($entity->getSubtype()) {
                    case 'blog':
                        return $entity->getPermaUrl();
                    default:
                        return $this->config->get('site_url') . 'newsfeed/' . $entity->getGuid();
                }
                // no break
            default:
                return $this->config->get('site_url') . 'newsfeed/' . $entity->getGuid();
        }
    }

    /**
     * Builds media URL.
     * @param Entity|Blog|Image|Video $entity - entity to build media URL for.
     * @return ?string - media URL for push notification.
     */
    protected function buildMediaUrl(Entity|Blog|Image|Video $entity): ?string
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
     * @param Entity|Blog|Image|Video $entity - entity to get username string for.
     * @return string - username string (@username).
     */
    protected function getOwnerUsernameString(Entity|Blog|Image|Video $entity): string
    {
        $username = $entity->getOwnerEntity()->getUsername();
        if (mb_strlen($username) > 45) {
            return '@' . mb_substr($username, 0, 45).'...';
        }
        return '@' . $username;
    }
}
