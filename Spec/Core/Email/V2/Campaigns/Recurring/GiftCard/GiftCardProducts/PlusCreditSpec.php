<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\GiftCardProducts;

use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\GiftCardProducts\PlusCredit;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class PlusCreditSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(PlusCredit::class);
    }

    public function it_should_build_content(User $user): void
    {
        $amount = 100;

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Minds');

        $this->setAmount($amount);
        $this->setSender($user);

        $this->buildContent()
            ->shouldBe("You've been gifted <b>$100 in Minds+ Credits</b> by <b>Minds</b> to use towards any Minds+ subscription you purchase!");
    }

    public function it_should_build_the_subject(User $user): void
    {
        $amount = 100;

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Minds');

        $this->setAmount($amount);
        $this->setSender($user);

        $this->buildSubject()
            ->shouldBe("You've been gifted $100 in Minds+ Credits by Minds");
    }
}
