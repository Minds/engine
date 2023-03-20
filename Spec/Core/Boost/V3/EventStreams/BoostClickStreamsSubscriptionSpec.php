<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3\EventStreams;

use DateTime;
use Minds\Core\Boost\V3\EventStreams\BoostClickStreamsSubscription;
use Minds\Core\Boost\V3\Models\Boost;
use PhpSpec\ObjectBehavior;
use Minds\Core\Boost\V3\Summaries\Manager as SummariesManager;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\Activity;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BoostClickStreamsSubscriptionSpec extends ObjectBehavior
{
    /** @var SummariesManager */
    private Collaborator $summariesManager;

    public function let(
        SummariesManager $summariesManager
    ) {
        $this->beConstructedWith($summariesManager);
        $this->summariesManager = $summariesManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostClickStreamsSubscription::class);
    }

    public function it_should_get_static_subscription_id()
    {
        $this->getSubscriptionId()->shouldBe('boost-clicks');
    }

    public function it_should_get_topic()
    {
        $this->getTopic()->shouldBeLike(new ActionEventsTopic());
    }

    public function it_should_get_static_topic_regex()
    {
        $this->getTopicRegex()->shouldBe('click');
    }

    public function it_should_not_consume_a_non_action_event(EventInterface $event)
    {
        $this->consume($event)->shouldBe(false);
    }

    public function it_should_not_consume_an_event_with_an_attached_non_boost_entity(
        ActionEvent $event,
        Activity $activity
    ) {
        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($activity);
        $this->consume($event)->shouldBe(false);
    }

    public function it_should_consume_and_handle_a_boost_created_event(
        ActionEvent $event,
        Boost $boost
    ) {
        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($boost);

        $this->summariesManager->incrementClicks(
            boost: $boost,
            date: Argument::type(DateTime::class)
        )->shouldBeCalled();

        $this->consume($event)->shouldBe(true);
    }
}
