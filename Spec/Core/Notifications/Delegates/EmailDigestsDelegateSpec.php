<?php

namespace Spec\Minds\Core\Notifications\Delegates;

use Minds\Core\Notifications\Delegates\EmailDigestsDelegate;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\EmailDigests;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EmailDigestsDelegateSpec extends ObjectBehavior
{
    protected $emailDigestsManager;

    public function let(EmailDigests\Manager $emailDigestsManager)
    {
        $this->beConstructedWith($emailDigestsManager);
        $this->emailDigestsManager = $emailDigestsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmailDigestsDelegate::class);
    }
    
    public function it_should_add_email_digest_on_add(Notification $notification)
    {
        $this->emailDigestsManager->addToQueue(Argument::that(function ($notification) {
            return true;
        }))
            ->shouldBeCalled();
        $this->onAdd($notification);
    }
}
