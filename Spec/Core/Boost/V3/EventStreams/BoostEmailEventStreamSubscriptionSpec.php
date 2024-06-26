<?php

declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3\EventStreams;

use PhpSpec\ObjectBehavior;
use Minds\Core\Log\Logger;
use Minds\Core\Email\V2\Campaigns\Recurring\BoostV3\BoostEmailer;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\EventStreams\BoostEmailEventStreamSubscription;
use PhpSpec\Wrapper\Collaborator;

class BoostEmailEventStreamSubscriptionSpec extends ObjectBehavior
{
    private Collaborator $boostEmailer;
    private Collaborator $logger;

    public function let(
        BoostEmailer $boostEmailer,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $boostEmailer,
            $logger
        );

        $this->boostEmailer = $boostEmailer;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostEmailEventStreamSubscription::class);
    }

    public function it_should_get_static_subscription_id()
    {
        $this->getSubscriptionId()->shouldBe('boost-email');
    }

    public function it_should_get_topic()
    {
        $this->getTopic()->shouldBeLike(new ActionEventsTopic());
    }

    public function it_should_get_static_topic_regex()
    {
        $this->getTopicRegex()->shouldBe('(boost_created|boost_rejected|boost_accepted|boost_completed|boost_cancelled)');
    }

    public function it_should_not_consume_a_non_action_event(EventInterface $event)
    {
        $this->consume($event)->shouldBe(false);
    }

    public function it_should_consume_and_handle_a_boost_created_event(
        ActionEvent $event,
        Boost $boost
    ) {
        $action = ActionEvent::ACTION_BOOST_CREATED;

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($boost);

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->boostEmailer->setBoost($boost)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);

        $this->boostEmailer->setTopic($action)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);
            
        $this->boostEmailer->send()
            ->shouldBeCalled();

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_a_boost_accepted_event(
        ActionEvent $event,
        Boost $boost
    ): void {
        $action = ActionEvent::ACTION_BOOST_ACCEPTED;

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($boost);

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->boostEmailer->setBoost($boost)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);

        $this->boostEmailer->setTopic($action)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);
            
        $this->boostEmailer->send()
            ->shouldBeCalled();

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_a_boost_rejected_event(
        ActionEvent $event,
        Boost $boost
    ): void {
        $action = ActionEvent::ACTION_BOOST_REJECTED;

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($boost);

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->boostEmailer->setBoost($boost)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);

        $this->boostEmailer->setTopic($action)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);
            
        $this->boostEmailer->send()
            ->shouldBeCalled();

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_a_boost_completed_event(
        ActionEvent $event,
        Boost $boost
    ): void {
        $action = ActionEvent::ACTION_BOOST_COMPLETED;

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($boost);

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->boostEmailer->setBoost($boost)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);

        $this->boostEmailer->setTopic($action)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);
            
        $this->boostEmailer->send()
            ->shouldBeCalled();

        $this->consume($event)->shouldBe(true);
    }

    public function it_should_consume_and_handle_a_boost_cancelled_event(
        ActionEvent $event,
        Boost $boost
    ): void {
        $action = ActionEvent::ACTION_BOOST_CANCELLED;

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($boost);

        $event->getAction()
            ->shouldBeCalled()
            ->willReturn($action);

        $this->boostEmailer->setBoost($boost)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);

        $this->boostEmailer->setTopic($action)
            ->shouldBeCalled()
            ->willReturn($this->boostEmailer);
            
        $this->boostEmailer->send()
            ->shouldBeCalled();

        $this->consume($event)->shouldBe(true);
    }
}
