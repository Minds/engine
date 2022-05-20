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
 *
 * @example usage:
 * - $pushNotification = $this->dailyDigestPushNotificationBuilder
 *       ->withEntity($entityResponse->first()->getEntity())
 *       ->build();
 */
class DailyDigestPushNotificationBuilder implements EntityPushNotificationBuilderInterface
{
    // instance entity.
    public $entity = null;

    /**
     * Constructor.
     * @param ?Config $config - config.
     */
    public function __construct(private ?Config $config = null)
    {
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Create new cloned instance with class-level variable $this->entity.
     * @param Entity|Blog|Image|Video $entity - entity to construct new instance with.
     * @return DailyDigestPushNotificationBuilder - cloned instance of $this.
     */
    public function withEntity(Entity|Blog|Image|Video $entity): self
    {
        $instance = clone $this;

        if ($entity->getType() === 'activity' && $entity->getEntity()) {
            $entity = $entity->getEntity();
        }
        
        $instance->entity = $entity;
        return $instance;
    }

    /**
     * Build a daily digest CustomPushNotification.
     * @return CustomPushNotification - populated CustomPushNotification.
     */
    public function build(): CustomPushNotification
    {
        return (new CustomPushNotification())
            ->setTitle($this->buildTitle(), 0, 65)
            ->setBody($this->buildBody())
            ->setUri($this->buildUri())
            ->setMedia($this->buildMediaUrl());
    }

    /**
     * Builds title.
     * @return string - title for push notification.
     */
    protected function buildTitle(): string
    {
        $usernameString = $this->getOwnerUsernameString();

        switch ($this->entity->getType()) {
            case 'object':
                switch ($this->entity->getSubtype()) {
                    case 'blog':
                        return $usernameString . ' posted a blog';
                    case 'image':
                        if (!$this->entity->getTitle() && !$this->entity->getDescription()) {
                            return ' '; // can't be blank, so insert a space char
                        }
                        return $usernameString . ' posted an image';
                    case 'video':
                        if (!$this->entity->getTitle() && !$this->entity->getDescription()) {
                            return ' '; // can't be blank, so insert a space char
                        }
                        return $usernameString . ' posted a video';
                }
                break;
            default:
                return $usernameString . ' posted';
        }
    }

    /**
     * Builds body.
     * @return ?string - body for push notification.
     */
    protected function buildBody(): ?string
    {
        $body = '';
        switch ($this->entity->getType()) {
            case 'activity':
                $body = $this->entity->getMessage();
                break;
            case 'object':
                $usernameString = $this->getOwnerUsernameString();

                switch ($this->entity->getSubtype()) {
                    case 'blog':
                        $body = $this->entity->getTitle();
                        break;
                    case 'image':
                        if (!$this->entity->getTitle() && !$this->entity->getDescription()) {
                            $body = $usernameString . ' posted an image';
                            break;
                        }
                        $body = $this->entity->getTitle() ? $this->entity->getTitle() : $this->entity->getDescription();
                        break;
                    case 'video':
                        if (!$this->entity->getTitle() && !$this->entity->getDescription()) {
                            $body = $usernameString . ' posted a video';
                            break;
                        }
                        $body = $this->entity->getTitle() ? $this->entity->getTitle() : $this->entity->getDescription();
                        break;
                }
                break;
            default:
                return '';
        }

        if (mb_strlen($body) > 173) {
            $body = mb_substr($body, 0, 170).'...';
        }

        return $body;
    }

    /**
     * Builds URI to serve as the push notification link.
     * @return string - URI for push notification.
     */
    protected function buildUri(): string
    {
        switch ($this->entity->getType()) {
            case 'object':
                switch ($this->entity->getSubtype()) {
                    case 'blog':
                        return $this->entity->getPermaUrl();
                    default:
                        return $this->config->get('site_url') . 'newsfeed/' . $this->entity->getGuid();
                }
                break;
            default:
                return $this->config->get('site_url') . 'newsfeed/' . $this->entity->getGuid();
        }
    }

    /**
     * Builds media URL.
     * @return ?string - media URL for push notification.
     */
    protected function buildMediaUrl(): ?string
    {
        switch ($this->entity->getType()) {
            case 'object':
                return $this->entity->getIconUrl('large');
            default:
                return '';
        }
    }

    /**
     * Gets username string.
     * @return string - username string (@username).
     */
    protected function getOwnerUsernameString(): string
    {
        $username = $this->entity->getOwnerEntity()->getUsername();
        if (mb_strlen($username) > 45) {
            return '@' . mb_substr($username, 0, 45).'...';
        }
        return '@' . $username;
    }
}
