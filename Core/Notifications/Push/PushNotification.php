<?php
namespace Minds\Core\Notifications\Push;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Entities\User;

/**
 */
class PushNotification
{
    /** @var Notification */
    protected $notification;

    /** @var Config */
    protected $config;

    /** @var DeviceSubscription */
    protected $deviceSubscription;

    /** @var int */
    protected $unreadCount = 0;

    public function __construct(Notification $notification, Config $config = null)
    {
        $this->notification = $notification;
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        $from = $this->notification->getFrom();
        $entity = $this->notification->getEntity();

        if (!$from) {
            throw new UndeliverableException("{$this->notification->getFromGuid()} was not found");
        }

        if (!$entity) {
            throw new UndeliverableException("{$this->notification->getEntityUrn()} was not found");
        }

        $entityOwnerGuid = $entity instanceof User ? (string) $entity->getGuid() : (string) $entity->getOwnerGuid();

        $verb = $pronoun = $noun = '';


        if ($entityOwnerGuid === (string) $this->notification->getToGuid()) {
            $pronoun = 'your';
        } else {
            $pronoun = 'their';
        }

        switch ($entity->getType()) {
            case 'comment':
                $noun = 'comment';
                break;
            case 'user':
                $noun = '';
                break;
            case 'object':
                $noun = $entity->getSubtype();
                break;
            default:
                $noun = 'post';
        }

        switch ($this->notification->getType()) {
            case NotificationTypes::TYPE_VOTE_UP:
                $verb = 'voted up';
                break;
            case NotificationTypes::TYPE_VOTE_DOWN:
                $verb = 'voted down';
                break;
            case NotificationTypes::TYPE_REMIND:
                $verb = 'reminded';
                break;
            case NotificationTypes::TYPE_QUOTE:
                $verb = 'quoted';
                $pronoun = 'your';
                break;
            case NotificationTypes::TYPE_COMMENT:
                $verb = 'commented on';
                break;
            case NotificationTypes::TYPE_TAG:
                $verb = 'tagged you in';
                break;
            case NotificationTypes::TYPE_SUBSCRIBE:
                $verb = 'subscribed to';
                $pronoun = '';
                $noun = 'you';
                break;
            default:
                throw new UndeliverableException("Invalid type");
        }

        $fromString = $from->getName();

        if ($this->notification->getMergedCount() === 1) {
            $other = $this->notification->getMergedFrom(1)[0];
            if ($other) {
                $fromString .= " and {$other->getName()}";
            }
        }

        if ($this->notification->getMergedCount() > 1) {
            $fromString .= " and {$this->notification->getMergedCount()} others";
        }

        return "$fromString $verb $pronoun $noun";
    }

	/**
     * @return string
     */
    public function getBody(): ?string
    {
        $entity = $this->notification->getEntity();
		$excerpt = '';

		 switch ($entity->getType()) {
            case 'comment':
                $excerpt = $entity->getBody();
                break;
            case 'object':
                $excerpt = $entity->getTitle();
                break;
            case 'activity':
                $excerpt = $entity->getMessage();
        }

		switch ($this->notification->getType()) {
			case NotificationTypes::TYPE_COMMENT:
				$excerpt = $this->notification->getData()['comment_excerpt'];
				break;
		}		

        return $excerpt;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        $entity = $this->notification->getEntity();
        switch ($entity->getType()) {
            case 'user':
                return $this->config->get('site_url') . $entity->getUsername();
            case 'comment':
                return $this->config->get('site_url') . 'newsfeed/' . $entity->getEntityGuid();
            case 'activity':
            case 'object':
                return $this->config->get('site_url') . 'newsfeed/' . $entity->getGuid();
            default:
                return '';
        }
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->notification->getFrom()->getIconURL('xlarge');
    }

    /**
     * @return string
     */
    public function getMedia(): ?string
    {
        $entity = $this->notification->getEntity();
        if (!$entity) { return ''; };

        return  $entity->getIconUrl('xlarge');
    }

    /**
     * Return the grouping id of the push notification
     * NOTE: this is not the collapsing key which replaces messages
     * @return string
     */
    public function getGroup(): string
    {
        return $this->notification->getType();
    }

    /**
     * @param DeviceSubscription $deviceSubscription
     * @return self
     */
    public function setDeviceSubscription(DeviceSubscription $deviceSubscription): self
    {
        $this->deviceSubscription = $deviceSubscription;
        return $this;
    }

    /**
     * @return DeviceSubscription
     */
    public function getDeviceSubscription(): DeviceSubscription
    {
        return $this->deviceSubscription;
    }

    /**
     * @param int $unreadCount
     * @return self
     */
    public function setUnreadCount(int $unreadCount): self
    {
        $this->unreadCount = $unreadCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getUnreadCount(): int
    {
        return $this->unreadCount;
    }

    /**
     * @return string
     */
    public function getMergeKey(): string
    {
        return $this->notification->getMergeKey();
    }
}
