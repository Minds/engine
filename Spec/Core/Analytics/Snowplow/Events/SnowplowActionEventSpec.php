<?php

namespace Spec\Minds\Core\Analytics\Snowplow\Events;

use Minds\Core\Analytics\Snowplow\Events\SnowplowActionEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SnowplowActionEventSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SnowplowActionEvent::class);
    }

    public function it_should_return_data()
    {
        $this->setAction('boost');

        $this->getData()
            ->shouldBe([
                'action' => 'boost',
            ]);
    }

    public function it_should_return_comment_guid()
    {
        $this->setAction('boost');
        $this->setCommentGuid('123');

        $this->getData()
            ->shouldBe([
                'action' => 'boost',
                'comment_guid' => '123'
            ]);
    }

    public function it_should_return_boost_rating()
    {
        $this->setAction('boost');
        $this->setBoostRating(1);

        $this->getData()
            ->shouldBe([
                'action' => 'boost',
                'boost_rating' => 1
            ]);
    }

    public function it_should_return_boost_reject_reason()
    {
        $this->setAction('boost');
        $this->setBoostRejectReason(2);

        $this->getData()
            ->shouldBe([
                'action' => 'boost',
                'boost_reject_reason' => 2
            ]);
    }
}
