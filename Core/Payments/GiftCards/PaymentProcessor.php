<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardPaymentFailedException;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as StripeIntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;

class PaymentProcessor
{
    private const SERVICE_FEE_PCT = 0;

    private ?StripeIntentsManagerV2 $intentsManager = null;

    public function __construct(
        private readonly Logger $logger
    ) {
    }

    /**
     * @param GiftCard $giftCard
     * @param string $paymentMethodId
     * @return string
     * @throws GiftCardPaymentFailedException
     */
    public function setupPayment(
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
            throw new GiftCardPaymentFailedException(previous: $e);
        }
    }

    private function createPaymentIntent(GiftCard $giftCard, string $paymentMethodId): PaymentIntent
    {
        return (new PaymentIntent())
            ->setUserGuid($giftCard->issuedByGuid)
            ->setAmount($giftCard->amount * 100)
            ->setPaymentMethod($paymentMethodId)
            ->setOffSession(true)
            ->setConfirm(false)
            ->setCaptureMethod('manual')
            ->setMetadata([
                'giftCardGuid' => $giftCard->guid,
                'giftCardIssuerGuid' => $giftCard->issuedByGuid,
                'is_manual_transfer' => false // transfer method, NOT capture method.
            ])
            ->setServiceFeePct(self::SERVICE_FEE_PCT)
            ->setStatementDescriptor("Minds Gift Card")
            ->setDescription("Minds Gift Card");
    }

    /**
     * @param string $paymentID
     * @param User $issuer
     * @return bool
     * @throws StripeTransferFailedException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     */
    public function capturePayment(string $paymentID, User $issuer): bool
    {
        return $this->getIntentsManager()->capturePaymentIntent($paymentID, $issuer);
    }

    private function getIntentsManager(): StripeIntentsManagerV2
    {
        return $this->intentsManager ??= new StripeIntentsManagerV2();
    }
}
