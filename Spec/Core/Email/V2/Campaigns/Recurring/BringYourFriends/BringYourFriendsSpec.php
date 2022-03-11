<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\BringYourFriends;

use Minds\Core\Experiments;
use Minds\Core\Email;
use Minds\Core\Email\V2\Campaigns\Recurring\BringYourFriends\BringYourFriends;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BringYourFriendsSpec extends ObjectBehavior
{
    private $mailer;
    private $experimentsManager;
    private $emailManager;

    public function let(Email\Mailer $mailer, Experiments\Manager $experimentsManager, Email\Manager $emailManager)
    {
        $this->beConstructedWith(null, $mailer, $experimentsManager, $emailManager);
        $this->mailer = $mailer;
        $this->experimentsManager = $experimentsManager;
        $this->emailManager = $emailManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BringYourFriends::class);
    }

    public function it_should_not_send_if_not_in_experiment(User $user)
    {
        $this->setUser($user);

        $this->emailManager->isSubscribed(Argument::any())
            ->willReturn(true);

        $user->isEmailConfirmed()
            ->willReturn(true);
        $user->get('enabled')
            ->willReturn('yes');
        $user->get('banned')
            ->willReturn('no');
        $user->get('guid')
            ->willReturn('123');

        $this->experimentsManager->setUser(Argument::any())
            ->willReturn($this->experimentsManager);
        $this->experimentsManager->isOn('minds-2966-email')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->mailer->send(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->send();
    }
}
