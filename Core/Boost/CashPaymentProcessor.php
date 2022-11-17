<?php

namespace Minds\Core\Boost;

use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Boost\Network\Boost as NetworkBoost;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;

/**
 * Handler for Boost cash payments - creation and accepting / cancelling.
 */
class CashPaymentProcessor
{
    /** @var int - service fee percent */
    const SERVICE_FEE_PERCENT = 0;

    public function __construct(
        private ?IntentsManagerV2 $intentsManager = null,
    ) {
        $this->intentsManager ??= new IntentsManagerV2();
    }

    /**
     * Setup network boost stripe payment intent.
     * @param string $paymentMethodId - payment method id (reference for the card to be used).
     * @param NetworkBoost $boost - boost to be boosted.
     * @return string - ID of payment intent.
     */
    public function setupNetworkBoostStripePayment(string $paymentMethodId, NetworkBoost $boost): string
    {
        $paymentIntent = $this->preparePaymentIntent($paymentMethodId, $boost);
        $intent = $this->intentsManager->add($paymentIntent);
        return $intent->getId();
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws StripeTransferFailedException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     */
    public function capturePaymentIntent(string $paymentIntentId): bool
    {
        return $this->intentsManager->capturePaymentIntent($paymentIntentId);
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelPaymentIntent(string $paymentIntentId): bool
    {
        return $this->intentsManager->cancelPaymentIntent($paymentIntentId);
    }

    /**
     * Prepare a new payment intent from a given boost.
     * @param string $paymentMethodId - payment method id (reference the card to be used).
     * @param NetworkBoost $boost - boost to have an intent prepared from.
     * @return PaymentIntent build intent object.
     */
    private function preparePaymentIntent(string $paymentMethodId, NetworkBoost $boost): PaymentIntent
    {
        $boostOwner = $boost->getOwner();
        $boostOwnerGuid = $boostOwner->getGuid();
        return (new PaymentIntent())
            ->setUserGuid($boostOwnerGuid)
            ->setAmount($boost->getBid())
            ->setPaymentMethod($paymentMethodId) // Reference to the users card.
            ->setOffSession(true) // Needs to be off session.
            ->setConfirm(false) // Do not immediately confirm the transaction.
            ->setCaptureMethod('manual') // Hold funds rather than capturing immediately.
            ->setMetadata([
                'boost_guid' => $boost->getGuid(),
                'boost_sender_guid' => $boostOwnerGuid,
                'boost_owner_guid' => $boostOwnerGuid,
                'boost_entity_guid' => $boost->getEntityGuid(),
                'boost_type' => $boost->getType(),
                'impressions' => $boost->getImpressions(),
                'is_manual_transfer' => false // transfer method, NOT capture method.
            ])
            ->setServiceFeePct(self::SERVICE_FEE_PERCENT)
            ->setStatementDescriptor('Boost')
            ->setDescription("Boost from @{$boostOwner->getUsername()}");
    }
}
