<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards;

use Exception;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\Stripe\Customers\ManagerV2 as StripeCustomersManagerV2;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as StripeIntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;

class PaymentProcessor
{
    private const SERVICE_FEE_PCT = 0;

    private ?StripeIntentsManagerV2 $intentsManager = null;

    public function __construct(
        private readonly StripeCustomersManagerV2 $customersManager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param GiftCard $giftCard
     * @param string $paymentMethodId
     * @return string
     * @throws Exception
     */
    public function processPayment(
        GiftCard $giftCard,
        string $paymentMethodId
    ): string {
        $this->logger->info("Processing payment", [
            'guid' => $giftCard->guid,
            'amount' => $giftCard->amount,
        ]);
        $paymentIntent = $this->createPaymentIntent(
            $giftCard,
            $paymentMethodId
        );

        try {
            $result = $this->getIntentsManager()->add($paymentIntent);

            $this->logger->info("Payment processed", [
                'guid' => $giftCard->guid,
                'amount' => $giftCard->amount,
            ]);

            return $result->getId();
        } catch (\Exception $e) {
            $this->logger->error("Error while processing payment", [
                'guid' => $giftCard->guid,
                'amount' => $giftCard->amount,
                'paymentMethodId' => $paymentMethodId,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
            ]);
            throw $e;
        }
    }

    private function createPaymentIntent(GiftCard $giftCard, string $paymentMethodId): PaymentIntent
    {
        return (new PaymentIntent())
            ->setUserGuid($giftCard->issuedByGuid)
            ->setAmount($giftCard->amount)
            ->setPaynmentMethod($paymentMethodId)
            ->setOffSession(true)
            ->setConfirm(true)
            ->setMetadata([
                'giftCardGuid' => $giftCard->guid,
                'giftCardIssuerGuid' => $giftCard->issuedByGuid,
            ])
            ->setServiceFeePct(self::SERVICE_FEE_PCT)
            ->setStatementDescriptor("Minds Gift Card")
            ->setDescription("Minds Gift Card");
    }

    private function getIntentsManager(): StripeIntentsManagerV2
    {
        return $this->intentsManager ??= new StripeIntentsManagerV2();
    }
}
