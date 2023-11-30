<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2;

use GuzzleHttp\Exception\GuzzleException;
use Iterator;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\InAppPurchases\Enums\InAppPurchasePaymentMethodIdsEnum;
use Minds\Core\Payments\InAppPurchases\Models\ProductPurchase;
use Minds\Core\Payments\V2\Enums\PaymentAffiliateType;
use Minds\Core\Payments\V2\Enums\PaymentMethod;
use Minds\Core\Payments\V2\Enums\PaymentStatus;
use Minds\Core\Payments\V2\Enums\PaymentType;
use Minds\Core\Payments\V2\Exceptions\InvalidPaymentMethodException;
use Minds\Core\Payments\V2\Exceptions\PaymentNotFoundException;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Referrals\ReferralCookie;
use Minds\Core\Wire\Wire;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\InAppPurchaseNotAcknowledgedException;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

class Manager
{
    private ?User $user = null;

    public function __construct(
        private ?Repository                    $repository = null,
        private ?ReferralCookie                $referralCookie = null,
        private ?Logger                        $logger = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);

        $this->referralCookie ??= new ReferralCookie();

        $this->logger ??= Di::_()->get('Logger');
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Creates a new Minds payment record
     * @param PaymentDetails $paymentDetails
     * @return void
     * @throws ServerErrorException
     */
    public function createPayment(PaymentDetails $paymentDetails): void
    {
        $this->repository->createPayment($paymentDetails);
    }

    /**
     * @param Boost $boost
     * @param string|null $iapTransaction
     * @return PaymentDetails
     * @throws InAppPurchaseNotAcknowledgedException
     * @throws InvalidPaymentMethodException
     * @throws NotImplementedException
     * @throws ServerErrorException
     * @throws GuzzleException
     */
    public function createPaymentFromBoost(Boost $boost, ?ProductPurchase $iapProductPurchaseDetails = null): PaymentDetails
    {
        $affiliateUserGuid = $this->referralCookie->withRouterRequest($this->getServerRequest())->getAffiliateGuid();
        $affiliateType = PaymentAffiliateType::REFERRAL_COOKIE;
        if (!$affiliateUserGuid || $affiliateUserGuid === (int) $this->user->getGuid()) {
            $affiliateUserGuid =
                $this->user->referrer && (time() - $this->user->time_created) < 365 * 86400
                    ? (int) $this->user->referrer
                    : null;
            $affiliateType = $affiliateUserGuid ? PaymentAffiliateType::SIGNUP : null;
        }

        $paymentMethod = match ($boost->getPaymentMethodId()) {
            InAppPurchasePaymentMethodIdsEnum::GOOGLE->value => PaymentMethod::ANDROID_IAP,
            InAppPurchasePaymentMethodIdsEnum::APPLE->value => PaymentMethod::IOS_IAP,
            default => PaymentMethod::getValidatedPaymentMethod($boost->getPaymentMethod()),
        };

        $paymentTxId = $boost->getPaymentTxId();
        if ($iapProductPurchaseDetails) {
            $paymentTxId = $iapProductPurchaseDetails->transactionId;
        }

        $paymentDetails = new PaymentDetails([
            'userGuid' => (int) $boost->getOwnerGuid(),
            'affiliateUserGuid' => $affiliateUserGuid,
            'affiliateType' => $affiliateType ?? null, // Only set if it's a valid type, otherwise 'null' is fine
            'paymentType' => PaymentType::BOOST_PAYMENT,
            'paymentMethod' => $paymentMethod,
            'paymentAmountMillis' => (int) ($boost->getPaymentAmount() * 1000), // In dollars, so multiply by 1000
            'paymentTxId' => $paymentTxId,
        ]);

        $this->createPayment($paymentDetails);

        return $paymentDetails;
    }

    /**
     * @param Wire $wire
     * @param string $paymentTxId
     * @param bool $isPlus
     * @param bool $isPro
     * @param Activity|null $sourceActivity
     * @return PaymentDetails
     * @throws InvalidPaymentMethodException
     * @throws ServerErrorException
     */
    public function createPaymentFromWire(
        Wire $wire,
        string $paymentTxId,
        bool $isPlus = false,
        bool $isPro = false,
        ?Activity $sourceActivity = null,
        bool $paidWithGiftCard = false
    ): PaymentDetails {
        $affiliateUserGuid = null;
        $paymentType = PaymentType::WIRE_PAYMENT;

        $paymentMethod = PaymentMethod::CASH;
        if ($isPlus || $isPro) {
            if ($sourceActivity) {
                $affiliateUserGuid = ((int) $sourceActivity->getOwnerGuid()) ?? null;
                $affiliateType = $affiliateUserGuid ? PaymentAffiliateType::MINDS_PLUS_POST : null;
            } else {
                $affiliateUserGuid = $this->referralCookie->withRouterRequest($this->getServerRequest())->getAffiliateGuid();
                $affiliateType = PaymentAffiliateType::REFERRAL_COOKIE;
                if (!$affiliateUserGuid || $affiliateUserGuid === (int) $wire->getSender()->getGuid()) {
                    $affiliateUserGuid =
                        $wire->getSender()->referrer && (time() - $wire->getSender()->time_created) < 365 * 86400
                        ? (int) $wire->getSender()->referrer
                        : null;
                    $affiliateType = $affiliateUserGuid ? PaymentAffiliateType::SIGNUP : null;
                }
            }

            if ($isPlus) {
                $paymentType = PaymentType::MINDS_PLUS_PAYMENT;
            }
            if ($isPro) {
                $paymentType = PaymentType::MINDS_PRO_PAYMENT;
            }

            if ($paidWithGiftCard) {
                $paymentMethod = PaymentMethod::GIFT_CARD;
            }
        } elseif ($paidWithGiftCard) {
            throw new InvalidPaymentMethodException('Cannot use gift card as payment method for wire');
        }

        $paymentDetails = new PaymentDetails([
            'userGuid' => (int) $wire->getSender()->getGuid(),
            'affiliateUserGuid' => $affiliateUserGuid,
            'affiliateType' => $affiliateType ?? null, // Only set if it's a valid type, otherwise 'null' is fine
            'paymentType' => $paymentType,
            'paymentMethod' => $paymentMethod,
            'paymentAmountMillis' => (int) ($wire->getAmount() * 10), // Already in cents, so multiply by 10
            'paymentTxId' => $paymentTxId,
            'paymentStatus' => !$wire->getTrialDays() ? PaymentStatus::COMPLETED : PaymentStatus::PENDING,
            'isCaptured' => !$wire->getTrialDays(), // Do not capture trial wires
        ]);

        $this->createPayment($paymentDetails);

        return $paymentDetails;
    }

    /**
     * @param int $paymentGuid
     * @param int $paymentStatus
     * @param bool $isCaptured
     * @return void
     * @throws PaymentNotFoundException
     * @throws ServerErrorException
     */
    public function updatePaymentStatus(int $paymentGuid, int $paymentStatus, bool $isCaptured = false): void
    {
        $this->repository->updatePaymentStatus(
            paymentGuid: $paymentGuid,
            paymentStatus: $paymentStatus,
            isCaptured: $isCaptured
        );
    }

    /**
     * Updates the payment tx for payment
     */
    public function updatePaymentTxId(int $paymentGuid, string $paymentTxId): bool
    {
        return $this->repository->updatePaymentTxId(
            paymentGuid: $paymentGuid,
            paymentTxId: $paymentTxId,
        );
    }

    /**
     * @return ServerRequest
     */
    private function getServerRequest(): ServerRequest
    {
        return ServerRequestFactory::fromGlobals();
    }

    /**
     * @param PaymentOptions $paymentOptions
     * @return Iterator
     * @throws ServerErrorException
     */
    public function getPaymentsAffiliatesEarnings(
        PaymentOptions $paymentOptions
    ): Iterator {
        return $this->repository->getPaymentsAffiliatesEarnings($paymentOptions);
    }
}
