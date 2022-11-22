<?php

namespace Spec\Minds\Core\Boost;

use Minds\Core\Boost\CashPaymentProcessor;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CashPaymentProcessorSpec extends ObjectBehavior
{
    /** @var IntentsManagerV2 */
    private $intentsManager;

    public function let(
        IntentsManagerV2 $intentsManager
    ) {
        $this->intentsManager = $intentsManager;
        $this->beConstructedWith($intentsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CashPaymentProcessor::class);
    }

    public function it_should_setup_a_network_boost_payment_intent(
        Boost $boost,
        User $owner,
        PaymentIntent $paymentIntent
    ) {
        $paymentMethodId = 'pay_123';
        $returnedPaymentIntentId = 'pay_intent_234';
        $boostOwnerGuid = '123';
        $boostOwnerUsername = 'test_account';
        $boostGuid = '234';
        $boostEntityGuid = '345';
        $boostType = 'newsfeed';
        $boostImpressions = 2000;
        $boostBid = 2;

        $owner->getGuid()
            ->shouldBeCalled()
            ->willReturn($boostOwnerGuid);

        $owner->getUsername()
            ->shouldBeCalled()
            ->willReturn($boostOwnerUsername);

        $boost->getOwner()
            ->shouldBeCalled()
            ->willReturn($owner);

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($boostGuid);

        $boost->getBid()
            ->shouldBeCalled()
            ->willReturn($boostBid);

        $boost->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($boostEntityGuid);

        $boost->getType()
            ->shouldBeCalled()
            ->willReturn($boostType);

        $boost->getImpressions()
            ->shouldBeCalled()
            ->willReturn($boostImpressions);

        $paymentIntent->getId()
            ->shouldBeCalled()
            ->willReturn($returnedPaymentIntentId);

        $this->intentsManager->add(Argument::that(function ($arg) use (
            $paymentMethodId,
            $boostOwnerGuid,
            $boostOwnerUsername,
            $boostGuid,
            $boostEntityGuid,
            $boostType,
            $boostImpressions,
            $boostBid
        ) {
            $metadata = $arg->getMetadata();
            return $arg->getUserGuid() === $boostOwnerGuid &&
                $arg->getAmount() === $boostBid &&
                $arg->getPaymentMethod() === $paymentMethodId &&
                $arg->getOffSession() &&
                !$arg->getConfirm() &&
                $arg->getCaptureMethod() === 'manual' &&
                $metadata['boost_guid'] === $boostGuid &&
                $metadata['boost_sender_guid'] === $boostOwnerGuid &&
                $metadata['boost_owner_guid'] === $boostOwnerGuid &&
                $metadata['boost_entity_guid'] === $boostEntityGuid &&
                $metadata['boost_type'] === $boostType &&
                $metadata['impressions'] === $boostImpressions &&
                $metadata['is_manual_transfer'] === false &&
                $arg->getServiceFeePct() === 0 &&
                $arg->getDescriptor() === 'Minds: Boost' &&
                $arg->getDescription() === "Boost from @$boostOwnerUsername";
        }))
            ->shouldBeCalled()
            ->willReturn($paymentIntent);

        $this->setupNetworkBoostStripePayment($paymentMethodId, $boost)->shouldBe($returnedPaymentIntentId);
    }

    public function it_should_capture_a_network_boost_payment_intent()
    {
        $paymentIntentId = '123';

        $this->intentsManager->capturePaymentIntent($paymentIntentId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->capturePaymentIntent($paymentIntentId)->shouldBe(true);
    }

    public function it_should_cancel_a_network_boost_payment_intent()
    {
        $paymentIntentId = '123';

        $this->intentsManager->cancelPaymentIntent($paymentIntentId)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cancelPaymentIntent($paymentIntentId)->shouldBe(true);
    }
}
