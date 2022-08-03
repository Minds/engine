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
 * Notification builder for top post notifications. Used to get a
 * CustomPushNotification populated with information for an unseen top post.
 *
 * @example usage:
 * - $pushNotification = $this->topPostPushNotificationBuilder
 *       ->withEntity($entityResponse->first()->getEntity())
 *       ->build();
 */
class TopPostPushNotificationBuilder implements EntityPushNotificationBuilderInterface
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
     * @return self - cloned instance of $this.
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
     * Build a top post CustomPushNotification.
     * @return CustomPushNotification - populated CustomPushNotification.
     */
    public function build(): CustomPushNotification
    {
        return (new CustomPushNotification())
            ->setTitle($this->buildTitle())
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
        $nameString = $this->getOwnerNameString();

        switch ($this->entity->getType()) {
            case 'activity':
                if ($this->isEntityALink()) {
                    return $nameString . ' posted a link';
                }
                break;
            case 'object':
                switch ($this->entity->getSubtype()) {
                    case 'blog':
                        return $nameString . ' posted a blog';
                    case 'image':
                        return $nameString . ' posted an image';
                    case 'video':
                        return $nameString . ' posted a video';
                }
                break;
        }

        return $nameString . ' posted';
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
                if ($this->isEntityALink()) {
                    $body = $this->entity->getTitle() ?: $this->entity->getMessage();
                } else {
                    $body = $this->entity->getMessage();
                }
                break;
            case 'object':
                switch ($this->entity->getSubtype()) {
                    case 'blog':
                        $body = $this->entity->getTitle();
                        break;
                    case 'image':
                        $body = $this->entity->getTitle() ?: $this->entity->getDescription() ?: '';
                        break;
                    case 'video':
                        $body = $this->entity->getTitle() ?: $this->entity->getDescription() ?: '';
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
     * Gets the owner's name or username
     * @return string - name string
     */
    protected function getOwnerNameString(): string
    {
        $entityOwner = $this->entity->getOwnerEntity();
        $ownerName = $entityOwner->getName();
        $ownerUsername = $entityOwner->getUsername();
        $name = $ownerName ?: '@' . $ownerUsername;

        if (mb_strlen($name) > 45) {
            return mb_substr($name, 0, 45).'...';
        }

        return $name;
    }

    /**
     * returns whether the entity is a link
     * @return bool
     */
    private function isEntityALink(): bool
    {
        if ($this->entity->getType() !== 'activity') {
            return false;
        }

        $message = trim($this->entity->getMessage());
        return ($this->entity->getPermaUrl() === $message) || ($this->entity->getPermaUrl() && !$message);
    }
}
