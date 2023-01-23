<?php
namespace Minds\Core\Notifications\Push;

use Minds\Core\Boost\V3\Models\Boost as BoostV3;
use Minds\Core\Boost\V3\Utils\BoostConsoleUrlBuilder;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities\User;

/**
 */
class PushNotification implements PushNotificationInterface
{
    /** @var Notification */
    protected $notification;

    /** @var Config */
    protected $config;

    /** @var DeviceSubscription */
    protected $deviceSubscription;

    /** @var int */
    protected $unreadCount = 0;

    private array $metadata = [];

    public function __construct(
        Notification $notification,
        Config $config = null,
        private ?BoostConsoleUrlBuilder $boostConsoleUrlBuilder = null
    ) {
        $this->notification = $notification;

        if (!$this->isValidNotification($notification)) {
            throw new UndeliverableException("Invalid Type: {$notification->getType()}");
        }

        $this->config = $config ?? Di::_()->get('Config');
        $this->boostConsoleUrlBuilder ??= Di::_()->get(BoostConsoleUrlBuilder::class);
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
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_CREATE:
                $verb = 'sent you a';
                $pronoun = '';
                $noun = 'Supermind offer';
                break;
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_ACCEPT:
                $verb = 'has replied to';
                $pronoun = 'your';
                $noun = 'Supermind offer';
                break;
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_REJECT:
                $verb = 'has declined';
                $pronoun = 'your';
                $noun = 'Supermind offer';
                break;
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_EXPIRING_SOON:
                return "Don't forget to review {$from->getName()}'s Supermind offer";
                break;
            // case NotificationTypes::TYPE_SUPERMIND_REQUEST_EXPIRE:
            //     $verb = 'missed';
            //     $pronoun = 'your';
            //     $noun = 'Supermind Offer';
            //     break;
                //repeat
            case NotificationTypes::TYPE_TOKEN_REWARDS_SUMMARY:
                return 'Minds Token Rewards';
            case NotificationTypes::TYPE_BOOST_ACCEPTED:
                return 'Your Boost is now running';
            case NotificationTypes::TYPE_BOOST_REJECTED:
                return 'Your Boost was rejected';
                break;
            case NotificationTypes::TYPE_BOOST_COMPLETED:
                return 'Your Boost is complete';
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

        if (!$pronoun) {
            return "$fromString $verb $noun";
        }

        return "$fromString $verb $pronoun $noun";
    }

    /**
     * @return string
     */
    public function getBody(): ?string
    {
        if (in_array($this->notification->getType(), [
            NotificationTypes::TYPE_BOOST_ACCEPTED,
            NotificationTypes::TYPE_BOOST_REJECTED,
            NotificationTypes::TYPE_BOOST_COMPLETED
        ], true)) {
            return '';
        }

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
            case NotificationTypes::TYPE_TOKEN_REWARDS_SUMMARY:
                $data = $this->notification->getData();
                $excerpt = "🚀' You earned {$data['tokens_formatted']} tokens (\${$data['usd_formatted']}) yesterday";
                break;
        }

        return $excerpt;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        switch ($this->notification->getType()) {
            case NotificationTypes::TYPE_SUBSCRIBE:
                return $this->config->get('site_url') . 'notifications';
            case NotificationTypes::TYPE_BOOST_ACCEPTED:
            case NotificationTypes::TYPE_BOOST_COMPLETED:
                return $this->getBoostConsoleUrl();
        }

        $entity = $this->notification->getEntity();

        if ($entity instanceof BoostV3) {
            $entity = $entity->getEntity();
        }

        switch ($entity->getType()) {
            case 'user':
                return $this->config->get('site_url') . $entity->getUsername();
            case 'comment':
                return $this->config->get('site_url') . 'newsfeed/' . $entity->getEntityGuid() . '?focusedCommentUrn=' . $entity->getUrn();
            case 'supermind':
                if ($entity instanceof SupermindRequest && $entity->getStatus() === SupermindRequestStatus::ACCEPTED) {
                    return $this->config->get('site_url') . 'newsfeed/' . $entity->getReplyActivityGuid();
                }
                return $this->config->get('site_url') . 'supermind/' . $entity->getGuid();
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
        return $this->notification->getFrom()->getIconURL('large');
    }

    /**
     * @return string
     */
    public function getMedia(): ?string
    {
        $entity = $this->notification->getEntity();
        if (!$entity) {
            return null;
        };

        switch ($entity->getType()) {
            case 'object':
                return $entity->getIconUrl('xlarge');
            break;
            case 'activity':
                return $entity->getThumbnail();
            break;
        }
            
        return null;
    }

    /**
     * Return the grouping id of the push notification
     * NOTE: this is not the collapsing key which replaces messages
     * @return string
     */
    public function getGroup(): string
    {
        return $this->notification->getGroupingType();
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return string
     */
    public function getMergeKey(): string
    {
        return $this->notification->getMergeKey();
    }

    /**
     * @return Notification
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }

    /**
     * @return string
     */
    public function getUserGuid(): string
    {
        return (string) $this->notification->getToGuid();
    }

    /**
     * @return bool
     */
    protected function isValidNotification(Notification $notification): bool
    {
        switch ($notification->getType()) {
            case NotificationTypes::TYPE_VOTE_UP:
            case NotificationTypes::TYPE_VOTE_DOWN:
            case NotificationTypes::TYPE_REMIND:
            case NotificationTypes::TYPE_QUOTE:
            case NotificationTypes::TYPE_COMMENT:
            case NotificationTypes::TYPE_TAG:
            case NotificationTypes::TYPE_SUBSCRIBE:
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_CREATE:
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_ACCEPT:
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_REJECT:
            case NotificationTypes::TYPE_SUPERMIND_REQUEST_EXPIRING_SOON:
            // case NotificationTypes::TYPE_SUPERMIND_REQUEST_EXPIRE:
            case NotificationTypes::TYPE_TOKEN_REWARDS_SUMMARY:
            case NotificationTypes::TYPE_BOOST_ACCEPTED:
            case NotificationTypes::TYPE_BOOST_REJECTED:
            case NotificationTypes::TYPE_BOOST_COMPLETED:
                return true;
        }
        return false;
    }

    /**
     * Gets boost console URL.
     * @return string url for boost console.
     */
    private function getBoostConsoleUrl(): string
    {
        $boost = $this->notification->getEntity();
        if (!$boost instanceof BoostV3) {
            $baseUrl = $this->config->get('site_url');
            return $baseUrl . 'boost/console/newsfeed/history';
        }
        return $this->boostConsoleUrlBuilder->build($boost);
    }
}
