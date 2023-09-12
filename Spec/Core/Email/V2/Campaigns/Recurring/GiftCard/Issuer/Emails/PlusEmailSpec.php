<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails;

use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emails\PlusEmail;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PlusEmailSpec extends ObjectBehavior
{
    private Collaborator $config;

    public function let(Config $config)
    {
        $this->config = $config;
        $this->beConstructedWith($this->config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PlusEmail::class);
    }

    public function it_should_build_body_content_array_for_monthly_amount(): void
    {
        $amount = 10;
        $yearlyUsd = 110;
        $monthlyUsd = 10;

        $this->setAmount($amount);

        $this->config->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' =>  [
                    'yearly' => [
                        'usd' => $yearlyUsd
                    ],
                    'monthly' => [
                        'usd' => $monthlyUsd
                    ]
                ]
            ]);

        $this->buildBodyContentArray()
            ->shouldBe([
                "Thanks for gifting <b>Minds+ ($10 for 1 month)</b> to someone lucky. If you selected a recipient during checkout, we've already notified them with details on how they can claim the gift.",
                "<b>Or you can share this claim link</b> with them, whether or not they already have a Minds account."
            ]);
    }

    public function it_should_build_body_content_array_for_yearly_amount(): void
    {
        $amount = 110;
        $yearlyUsd = 110;
        $monthlyUsd = 10;

        $this->setAmount($amount);

        $this->config->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' =>  [
                    'yearly' => [
                        'usd' => $yearlyUsd
                    ],
                    'monthly' => [
                        'usd' => $monthlyUsd
                    ]
                ]
            ]);

        $this->buildBodyContentArray()
            ->shouldBe([
                "Thanks for gifting <b>Minds+ ($110 for 1 year)</b> to someone lucky. If you selected a recipient during checkout, we've already notified them with details on how they can claim the gift.",
                "<b>Or you can share this claim link</b> with them, whether or not they already have a Minds account."
            ]);
    }

    public function it_should_build_body_content_array_for_slightly_less_than_yearly_amount(): void
    {
        $amount = 109;
        $yearlyUsd = 110;
        $monthlyUsd = 10;

        $this->setAmount($amount);

        $this->config->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' =>  [
                    'yearly' => [
                        'usd' => $yearlyUsd
                    ],
                    'monthly' => [
                        'usd' => $monthlyUsd
                    ]
                ]
            ]);

        $this->buildBodyContentArray()
            ->shouldBe([
                "Thanks for gifting <b>Minds+ ($109 for 1 month)</b> to someone lucky. If you selected a recipient during checkout, we've already notified them with details on how they can claim the gift.",
                "<b>Or you can share this claim link</b> with them, whether or not they already have a Minds account."
            ]);
    }

    public function it_should_build_body_content_array_for_slightly_more_than_yearly_amount(): void
    {
        $amount = 111;
        $yearlyUsd = 110;
        $monthlyUsd = 10;

        $this->setAmount($amount);

        $this->config->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' =>  [
                    'yearly' => [
                        'usd' => $yearlyUsd
                    ],
                    'monthly' => [
                        'usd' => $monthlyUsd
                    ]
                ]
            ]);

        $this->buildBodyContentArray()
            ->shouldBe([
                "Thanks for gifting <b>Minds+ ($111 for 1 year)</b> to someone lucky. If you selected a recipient during checkout, we've already notified them with details on how they can claim the gift.",
                "<b>Or you can share this claim link</b> with them, whether or not they already have a Minds account."
            ]);
    }

    public function it_should_build_body_content_when_amount_is_less_than_monthly(): void
    {
        $amount = 1;
        $yearlyUsd = 110;
        $monthlyUsd = 10;

        $this->setAmount($amount);

        $this->config->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' =>  [
                    'yearly' => [
                        'usd' => $yearlyUsd
                    ],
                    'monthly' => [
                        'usd' => $monthlyUsd
                    ]
                ]
            ]);

        $this->buildBodyContentArray()
            ->shouldBe([
                "Thanks for gifting <b>Minds+ </b> to someone lucky. If you selected a recipient during checkout, we've already notified them with details on how they can claim the gift.",
                "<b>Or you can share this claim link</b> with them, whether or not they already have a Minds account."
            ]);
    }

    public function it_should_build_the_subject(User $user): void
    {
        $this->buildSubject()
            ->shouldBe("Your Minds+ gift is on the way");
    }
}
