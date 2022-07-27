<?php

namespace Spec\Minds\Core\Blockchain\Skale\Escrow;

use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Skale\Escrow\Participants;
use Minds\Entities\User;

class ParticipantsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Participants::class);
    }

    public function it_should_set_and_get_sender(User $user)
    {
        $this->setSender($user);
        $this->getSender()->shouldBe($user);
    }

    public function it_should_set_and_get_receiver(User $user)
    {
        $this->setReceiver($user);
        $this->getReceiver()->shouldBe($user);
    }
}
