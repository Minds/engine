<?php

namespace Spec\Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Boost\Campaigns\Delegates\PaymentsDelegate;
use Minds\Core\Boost\Campaigns\Metrics;
use Minds\Core\Boost\Campaigns\Payments\Onchain;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Core\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PaymentsDelegateSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;
    /** @var Onchain */
    protected $onchainPayments;
    /** @var Metrics */
    protected $metrics;

    public function let(Config $config, Onchain $onchainPayments, Metrics $metrics)
    {
        $this->beConstructedWith($config, $onchainPayments, $metrics);
        $this->config = $config;
        $this->onchainPayments = $onchainPayments;
        $this->metrics = $metrics;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PaymentsDelegate::class);
    }

    public function it_should_throw_an_exception_if_there_is_no_budget(Campaign $campaign)
    {
        $campaign->getBudget()->shouldBeCalled()->willReturn(0);
        $this->shouldThrow(CampaignException::class)->duringValidateBudget($campaign);
    }

    public function it_should_throw_an_exception_if_budget_type_is_invalid(Campaign $campaign)
    {
        $campaign->getBudget()->shouldBeCalled()->willReturn(2);
        $campaign->getBudgetType()->shouldBeCalled()->willReturn(null);
        $this->shouldThrow(CampaignException::class)->duringValidateBudget($campaign);
    }

    public function it_should_validate_budget(Campaign $campaign)
    {
        $campaign->getBudget()->shouldBeCalled()->willReturn(2);
        $campaign->getBudgetType()->shouldBeCalled()->willReturn('tokens');
        $this->validateBudget($campaign);
    }

    public function it_should_validate_payments(Campaign $campaign)
    {
        $this->validatePayments($campaign);
    }

    public function it_should_throw_an_exception_on_invalid_payment_signature(Campaign $campaign)
    {
        $payload = [];
        $campaign->getBudgetType()->shouldBeCalled()->willReturn('tokens');
        $this->shouldThrow(CampaignException::class)->duringPay($campaign, $payload);
    }

    public function it_should_throw_an_exception_if_error_registering_payment(Campaign $campaign)
    {
        $payload = [
            'txHash' => 'abc123',
            'address' => '0x1234',
            'amount' => '1'
        ];
        $campaign->getBudgetType()->shouldBeCalled()->willReturn('tokens');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getGuid()->shouldBeCalled()->willReturn(23456);
        $this->onchainPayments->record(Argument::type(Payment::class))->willThrow(\Exception::class);
        $this->shouldThrow(CampaignException::class)->duringPay($campaign, $payload);
    }

    public function it_should_register_a_payment(Campaign $campaign)
    {
        $payload = [
            'txHash' => 'abc123',
            'address' => '0x1234',
            'amount' => '1'
        ];
        $campaign->getBudgetType()->shouldBeCalled()->willReturn('tokens');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getGuid()->shouldBeCalled()->willReturn(23456);
        $this->onchainPayments->record(Argument::type(Payment::class))->shouldBeCalled();
        $campaign->pushPayment(Argument::type(Payment::class))->shouldBeCalled();
        $this->pay($campaign, $payload);
    }

    public function it_should_not_refund_if_below_refund_threshold(Campaign $campaign, Payment $payment1)
    {
        $payments = [
            $payment1
        ];

        $this->onchainPayments->refund(Argument::type(Payment::class))->shouldNotBeCalled();
        $campaign->getPayments()->shouldBeCalled()->willReturn($payments);
        $payment1->getAmount()->shouldBeCalled()->willReturn(0.01);
        $payment1->getSource()->shouldBeCalled()->willReturn('something');

        $this->refund($campaign);
    }

    public function it_should_throw_an_exception_if_refund_fails(Campaign $campaign, Payment $payment1)
    {
        $payments = [
            $payment1
        ];

        $campaign->getPayments()->shouldBeCalled()->willReturn($payments);
        $payment1->getAmount()->shouldBeCalled()->willReturn(2);
        $payment1->getSource()->shouldBeCalled()->willReturn('something');

        $this->metrics->setCampaign($campaign)->shouldBeCalled()->willReturn($this->metrics);
        $this->metrics->getImpressionsMet()->shouldBeCalled()->willReturn(500);
        $campaign->cpm()->shouldBeCalled()->willReturn(1);
        $campaign->getBudgetType()->shouldBeCalled()->willReturn('tokens');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getGuid()->shouldBeCalled()->willReturn(23456);
        $this->onchainPayments->refund(Argument::type(Payment::class))->shouldBeCalled()->willThrow(\Exception::class);

        $this->shouldThrow(CampaignException::class)->duringRefund($campaign);
    }

    public function it_should_register_a_refund(Campaign $campaign, Payment $payment1)
    {
        $payments = [
            $payment1
        ];

        $campaign->getPayments()->shouldBeCalled()->willReturn($payments);
        $payment1->getAmount()->shouldBeCalled()->willReturn(2);
        $payment1->getSource()->shouldBeCalled()->willReturn('something');

        $this->metrics->setCampaign($campaign)->shouldBeCalled()->willReturn($this->metrics);
        $this->metrics->getImpressionsMet()->shouldBeCalled()->willReturn(500);
        $campaign->cpm()->shouldBeCalled()->willReturn(1);
        $campaign->getBudgetType()->shouldBeCalled()->willReturn('tokens');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getGuid()->shouldBeCalled()->willReturn(23456);
        $this->onchainPayments->refund(Argument::type(Payment::class))->shouldBeCalled();
        $campaign->pushPayment(Argument::type(Payment::class))->shouldBeCalled();

        $this->refund($campaign);
    }
}
