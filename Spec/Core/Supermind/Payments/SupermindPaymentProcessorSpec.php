<?php

namespace Spec\Minds\Core\Supermind\Payments;

use Minds\Core\Blockchain\Transactions\Transaction;
use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Intents\Intent;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Settings\Manager as SettingsManager;
use Minds\Core\Supermind\Payments\SupermindPaymentProcessor;
use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use Prophecy\Argument;

class SupermindPaymentProcessorSpec extends ObjectBehavior
{
    /** @var IntentsManagerV2 */
    private $intentsManager;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var OffchainTransactions */
    private $offchainTransactions;

    /** @var MindsConfig */
    private $mindsConfig;

    /** @var SettingsManager */
    private $settingsManager;

    public function let(
        IntentsManagerV2 $intentsManager,
        EntitiesBuilder $entitiesBuilder,
        OffchainTransactions $offchainTransactions,
        MindsConfig $mindsConfig,
        SettingsManager $settingsManager
    ) {
        $this->beConstructedWith(
            $intentsManager,
            $entitiesBuilder,
            $offchainTransactions,
            $mindsConfig,
            $settingsManager
        );

        $this->intentsManager = $intentsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->offchainTransactions = $offchainTransactions;
        $this->mindsConfig = $mindsConfig;
        $this->settingsManager = $settingsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindPaymentProcessor::class);
    }

    public function it_should_get_minimum_allowed_amount_for_cash(Settings $settings, User $user)
    {
        $paymentMethod = SupermindRequestPaymentMethod::CASH;
        $minCash = 42;

        $this->setUser($user);

        $this->settingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->settingsManager);

        $settings->getMinCash()
            ->shouldBeCalled()
            ->willReturn($minCash);

        $this->settingsManager->getSettings($paymentMethod)
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->getMinimumAllowedAmount($paymentMethod)
            ->shouldBe((double) $minCash);
    }

    public function it_should_get_minimum_allowed_amount_for_toklens(Settings $settings, User $user)
    {
        $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $minTokens = 84;

        $this->setUser($user);

        $this->settingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->settingsManager);

        $settings->getMinOffchainTokens()
            ->shouldBeCalled()
            ->willReturn($minTokens);

        $this->settingsManager->getSettings($paymentMethod)
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->getMinimumAllowedAmount($paymentMethod)
            ->shouldBe((double) $minTokens);
    }

    public function it_should_check_if_payment_amount_is_allowed(Settings $settings, User $user)
    {
        $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $paymentAmount = 84;
        $minTokens = 84;

        $this->setUser($user);

        $this->settingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->settingsManager);

        $settings->getMinOffchainTokens()
            ->shouldBeCalled()
            ->willReturn($minTokens);

        $this->settingsManager->getSettings($paymentMethod)
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->isPaymentAmountAllowed($paymentAmount, $paymentMethod)
            ->shouldBe(true);
    }

    public function it_should_check_if_payment_amount_is_NOT_allowed(Settings $settings, User $user)
    {
        $paymentMethod = SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $paymentAmount = 83;
        $minTokens = 84;

        $this->setUser($user);

        $this->settingsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->settingsManager);

        $settings->getMinOffchainTokens()
            ->shouldBeCalled()
            ->willReturn($minTokens);

        $this->settingsManager->getSettings($paymentMethod)
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->isPaymentAmountAllowed($paymentAmount, $paymentMethod)
            ->shouldBe(false);
    }

    public function it_should_setup_stripe_payment(
        SupermindRequest $request,
        Intent $intent,
        User $receiver
    ) {
        $paymentMethodId = SupermindRequestPaymentMethod::CASH;
        $guid = '123';
        $senderGuid = '234';
        $receiverGuid = '345';
        $merchant = [ 'id' => 'acc_123' ];
        $paymentAmount = 123;
        $serviceFeePercent = 14;

        $request->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $request->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $request->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $request->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $receiver->getMerchant()
            ->shouldBeCalled()
            ->willReturn($merchant);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($receiver);

        $intent->getId()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->mindsConfig->get('payments')
            ->shouldBeCalled()
            ->willReturn([
                'stripe' => [ 'service_fee_pct' => $serviceFeePercent ]
            ]);

        $this->intentsManager->add(Argument::that(function ($arg) use (
            $guid,
            $receiverGuid,
            $senderGuid,
            $paymentAmount,
            $merchant,
            $serviceFeePercent
        ) {
            return $arg->getUserGuid() === $senderGuid &&
                $arg->getAmount() === $paymentAmount * 100 &&
                $arg->isOffSession() &&
                !$arg->isConfirm() &&
                $arg->getCaptureMethod() === 'manual' &&
                $arg->getStripeAccountId() === $merchant['id'] &&
                $arg->getMetadata() === [
                    'supermind' => $guid,
                    'receiver_guid' => $receiverGuid,
                    'user_guid' => $senderGuid
                ] &&
                $arg->getServiceFeePct() === $serviceFeePercent &&
                $arg->getDescriptor() === 'Minds: Supermind';
        }))
            ->shouldBeCalled()
            ->willReturn($intent);

        $this->setupSupermindStripePayment($paymentMethodId, $request);
    }

    public function it_should_capture_a_payment_intent()
    {
        $paymentIntentId = 'pay_123';

        $this->intentsManager->capturePaymentIntent($paymentIntentId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->capturePaymentIntent($paymentIntentId)
            ->shouldBe(true);
    }

    public function it_should_cancel_a_payment_intent()
    {
        $paymentIntentId = 'pay_123';

        $this->intentsManager->cancelPaymentIntent($paymentIntentId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cancelPaymentIntent($paymentIntentId)
            ->shouldBe(true);
    }

    public function it_should_setup_an_offchain_payment(
        SupermindRequest $request,
        User $user,
        Transaction $transaction
    ) {
        $txId = 'offchain:123';
        $guid = '123';
        $senderGuid = '234';
        $receiverGuid = '345';
        $paymentAmount = 100;

        $request->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $request->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $request->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $request->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->offchainTransactions->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);
        
        $this->offchainTransactions->setAmount((string) BigNumber::toPlain($paymentAmount, 18)->neg())
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setType('supermind')
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setData([
            'supermind' => $guid,
            'sender_guid' => $senderGuid,
            'receiver_guid' => $receiverGuid
        ])
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn($txId);

        $this->offchainTransactions->create()
            ->shouldBeCalled()
            ->willReturn($transaction);

        $this->setupOffchainPayment($request)
            ->shouldBe($txId);
    }

    public function it_should_refund_an_offchain_payment(
        SupermindRequest $request,
        User $user,
        Transaction $transaction
    ) {
        $guid = '123';
        $senderGuid = '234';
        $receiverGuid = '345';
        $paymentAmount = 100;
        $txId = 'offchain:wire:123';

        $request->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $request->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $request->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $request->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $transaction->getTx()
            ->shouldBeCalled()
            ->willReturn($txId);

        $this->entitiesBuilder->single($senderGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->offchainTransactions->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);
        
        $this->offchainTransactions->setAmount((string) BigNumber::toPlain($paymentAmount, 18))
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setType('supermind')
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setData([
            'supermind' => $guid,
            'sender_guid' => $senderGuid,
            'receiver_guid' => $receiverGuid
        ])
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->create()
            ->shouldBeCalled()
            ->willReturn($transaction);

        $this->refundOffchainPayment($request)->shouldBe($txId);
    }

    public function it_should_credit_an_offchain_payment(
        SupermindRequest $request,
        User $user,
        Transaction $transaction
    ) {
        $guid = '123';
        $senderGuid = '234';
        $receiverGuid = '345';
        $paymentAmount = 100;

        $request->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $request->getSenderGuid()
            ->shouldBeCalled()
            ->willReturn($senderGuid);

        $request->getReceiverGuid()
            ->shouldBeCalled()
            ->willReturn($receiverGuid);

        $request->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $this->entitiesBuilder->single($receiverGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->offchainTransactions->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);
        
        $this->offchainTransactions->setAmount((string) BigNumber::toPlain($paymentAmount, 18))
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setType('supermind')
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setData([
            'supermind' => $guid,
            'sender_guid' => $senderGuid,
            'receiver_guid' => $receiverGuid
        ])
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->create()
            ->shouldBeCalled()
            ->willReturn($transaction);

        $this->creditOffchainPayment($request);
    }
}
