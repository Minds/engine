<?php
/**
 * This subscription will build emails/notifications from stream events
 */
namespace Minds\Core\Supermind;

use Minds\Common\SystemUser;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Minds\Core\Config;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Email\V2\Campaigns\Recurring\Supermind\Supermind as SupermindEmailer;

class SupermindEventStreamsSubscription implements SubscriptionInterface
{
    /** @var Logger */
    protected $logger;

    /** @var Core\Config */
    protected $config;

    /** @var SupermindEmailer */
    protected $supermindEmailer;

    public function __construct(Logger $logger = null, Config $config = null, SupermindEmailer $supermindEmailer = null)
    {
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
        $this->supermindEmailer = $supermindEmailer ?? new SupermindEmailer();
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'supermind';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new ActionEventsTopic();
    }

    /**
     * Returns topic regex, scoping subscription to events we want to subscribe to.
     * @return string topic regex
     */
    public function getTopicRegex(): string
    {
        return '(supermind_request_create|supermind_request_accept|supermind_request_reject|supermind_request_expire|supermind_request_expiring_soon)';
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $this->logger->info("Consuming a {$event->getAction()} action");

        /** @var SupermindRequest */
        $supermindRequest = $event->getEntity();

        $this->supermindEmailer
            ->setSupermindRequest($supermindRequest);

        switch ($event->getAction()) {
            case ActionEvent::ACTION_SUPERMIND_REQUEST_CREATE:
                $this->logger->info("Dispatching supermind_request_sent to SupermindEmailer");
                $this->supermindEmailer
                    ->setTopic('supermind_request_sent')
                    ->send();

                $this->logger->info("Dispatching wire_received to SupermindEmailer");
                $this->supermindEmailer
                    /**
                     * Until a larger refactor of the email system, we are sending
                     * the topic as wire_received for the sake of email settings.
                     */
                    // ->setTopic('supermind_request_received')
                    ->setTopic('wire_received')
                    ->send();
                break;

            case ActionEvent::ACTION_SUPERMIND_REQUEST_ACCEPT:
                $this->logger->info("Dispatching supermind_request_accepted to SupermindEmailer");
                $this->supermindEmailer
                    ->setTopic('supermind_request_accepted')
                    ->send();
                break;

            case ActionEvent::ACTION_SUPERMIND_REQUEST_REJECT:
                $this->logger->info("Dispatching supermind_request_rejected to SupermindEmailer");
                $this->supermindEmailer
                    ->setTopic('supermind_request_rejected')
                    ->send();
                break;

            case ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRE:
                $this->logger->info("Dispatching supermind_request_expired to SupermindEmailer");
                $this->supermindEmailer
                    ->setTopic('supermind_request_expired')
                    ->send();
                break;

            case ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRING_SOON:
                $this->logger->info("Dispatching supermind_request_expiring_soon to SupermindEmailer");
                $this->supermindEmailer
                    ->setTopic('supermind_request_expiring_soon')
                    ->send();
                break;

            default:
                $this->logger->info("Not dispatching to SupermindEmailer");
                return true; // Do nothing
        }
        return true;
    }
}
