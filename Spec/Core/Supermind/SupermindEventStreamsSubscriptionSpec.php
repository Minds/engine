<?php

namespace Spec\Minds\Core\Supermind;

use PhpSpec\ObjectBehavior;
use Minds\Core\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Email\V2\Campaigns\Recurring\Supermind\Supermind as SupermindEmailer;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindEventStreamsSubscription;
use Prophecy\Argument;

class SupermindEventStreamsSubscriptionSpec extends ObjectBehavior
{
    /** @var Logger */
    private $logger;

    /** @var Config */
    private $config;

    /** @var SupermindEmailer */
    private $supermindEmailer;

    public function let(
        Logger $logger,
        Config $config,
        SupermindEmailer $supermindEmailer
    ) {
        $this->beConstructedWith(
            $logger,
            $config,
            $supermindEmailer
        );

        $this->logger = $logger;
        $this->config = $config;
        $this->supermindEmailer = $supermindEmailer;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindEventStreamsSubscription::class);
    }

    public function it_should_get_static_subscription_id()
    {
        $this->getSubscriptionId()->shouldBe('supermind');
    }

    public function it_should_get_topic()
    {
        $this->getTopic()->shouldBeLike(new ActionEventsTopic());
    }

    public function it_should_get_static_topic_regex()
    {
        $this->getTopicRegex()->shouldBe('(supermind_request_create|supermind_request_accept|supermind_request_reject|supermind_request_expire|supermind_request_expiring_soon)');
    }

    // consume

    public function it_should_not_consume_a_non_action_event(EventInterface $event)
    {
        $this->consume($event)->shouldBe(false);
    }

    public function it_should_consume_and_handle_a_created_event(
        ActionEvent $event,
        SupermindRequest $supermindRequest
    ) {
        $action = ActionEvent::ACTION_SUPERMIND_REQUEST_CREATE;

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->supermindEmailer->setSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->supermindEmailer->setTopic('supermind_request_sent')
            ->shouldBeCalled()
            ->willReturn($this->supermindEmailer);

        
        $this->supermindEmailer->setTopic('wire_received')
            ->shouldBeCalled()
            ->willReturn($this->supermindEmailer);
        
        $this->supermindEmailer->send()
            ->shouldBeCalledTimes(2);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_an_accepted_event(
        ActionEvent $event,
        SupermindRequest $supermindRequest
    ) {
        $action = ActionEvent::ACTION_SUPERMIND_REQUEST_ACCEPT;

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->supermindEmailer->setSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->supermindEmailer->setTopic('supermind_request_accepted')
            ->shouldBeCalled()
            ->willReturn($this->supermindEmailer);

        $this->supermindEmailer->send()
            ->shouldBeCalledTimes(1);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_a_rejected_event(
        ActionEvent $event,
        SupermindRequest $supermindRequest
    ) {
        $action = ActionEvent::ACTION_SUPERMIND_REQUEST_REJECT;

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->supermindEmailer->setSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->supermindEmailer->setTopic('supermind_request_rejected')
            ->shouldBeCalled()
            ->willReturn($this->supermindEmailer);

        $this->supermindEmailer->send()
            ->shouldBeCalledTimes(1);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_an_expire_event(
        ActionEvent $event,
        SupermindRequest $supermindRequest
    ) {
        $action = ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRE;

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->supermindEmailer->setSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->supermindEmailer->setTopic('supermind_request_expired')
            ->shouldBeCalled()
            ->willReturn($this->supermindEmailer);

        $this->supermindEmailer->send()
            ->shouldBeCalledTimes(1);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_an_expiring_soon_event(
        ActionEvent $event,
        SupermindRequest $supermindRequest
    ) {
        $action = ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRING_SOON;

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->supermindEmailer->setSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->supermindEmailer->setTopic('supermind_request_expiring_soon')
            ->shouldBeCalled()
            ->willReturn($this->supermindEmailer);

        $this->supermindEmailer->send()
            ->shouldBeCalledTimes(1);

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_an_unsupported_event(
        ActionEvent $event,
        SupermindRequest $supermindRequest
    ) {
        $action = 'unknown/default';

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($supermindRequest);

        $this->supermindEmailer->setSupermindRequest($supermindRequest)
            ->shouldBeCalled();

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->supermindEmailer->setTopic(Argument::type('string'))
            ->shouldNotBeCalled();

        $this->supermindEmailer->send()
            ->shouldNotBeCalled();

        $this->consume($event)->shouldBe(true);
    }
}
