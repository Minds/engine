<?php

declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3\Utils;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Payments\Manager as PaymentManager;
use Minds\Core\Log\Logger;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config;
use Minds\Core\Payments\Models\Payment;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class BoostReceiptUrlBuilderSpec extends ObjectBehavior
{
    private Collaborator $paymentManager;
    private Collaborator $config;
    private Collaborator $logger;

    public function let(
        PaymentManager $paymentManager,
        Config $config,
        Logger $logger,
    ): void {
        $this->paymentManager = $paymentManager;
        $this->config = $config;
        $this->logger = $logger;

        $this->beConstructedWith(
            $paymentManager,
            $config,
            $logger
        );
    }

    public function it_should_build_a_url_for_cash(
        Boost $boost,
        Payment $payment
    ): void {
        $paymentMethod = BoostPaymentMethod::CASH;
        $txId = 'pay_123';
        $receiptUrl = '~url~';

        $boost->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $boost->getPaymentTxId()
            ->shouldBeCalled()
            ->willReturn($txId);

        $payment->getReceiptUrl()
            ->shouldBeCalled()
            ->willReturn($receiptUrl);
 
        $this->paymentManager->getPaymentById($txId)
            ->shouldBeCalled()
            ->willReturn($payment);

        $this->setBoost($boost)->build()->shouldBe($receiptUrl);
    }


    public function it_should_build_a_url_for_onchain(Boost $boost)
    {
        $paymentMethod = BoostPaymentMethod::ONCHAIN_TOKENS;
        $txId = '0x00';
        $receiptUrl = "https://etherscan.io/tx/$txId";

        $boost->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $boost->getPaymentTxId()
            ->shouldBeCalled()
            ->willReturn($txId);

        $this->setBoost($boost)->build()->shouldBe($receiptUrl);
    }

    public function it_should_not_build_a_url_for_offchain(Boost $boost)
    {
        $paymentMethod = BoostPaymentMethod::OFFCHAIN_TOKENS;

        $boost->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $this->setBoost($boost)->build()->shouldBe('');
    }
}
