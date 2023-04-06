<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2;

use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\V2\Enums\PaymentMethod;
use Minds\Core\Payments\V2\Enums\PaymentType;
use Minds\Core\Payments\V2\Exceptions\InvalidPaymentMethodException;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Wire\Wire;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;

class Manager
{
    private ?User $user = null;

    public function __construct(
        private ?Repository $repository = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Logger $logger = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');

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
     * @return void
     * @throws InvalidPaymentMethodException
     * @throws ServerErrorException
     */
    public function createPaymentFromBoost(Boost $boost): void
    {
        $request = $this->getServerRequest();
        $affiliateUser = isset($request->getCookieParams()['referrer']) ? $this->entitiesBuilder->getByUserByIndex($request->getCookieParams()['referrer']) : null;
        $affiliateUserGuid = (int) $affiliateUser?->getGuid() ?? null;
        if (!$affiliateUserGuid && $this->user->getGuid() === $boost->getOwnerGuid()) {
            $affiliateUserGuid =
                $this->user->referrer && (time() - $this->user->time_created) < 365 * 86400
                    ? (int) $this->user->referrer
                    : null;
        }

        $paymentDetails = new PaymentDetails([
            'userGuid' => (int) $boost->getOwnerGuid(),
            'affiliateUserGuid' => $affiliateUserGuid,
            'paymentType' => PaymentType::BOOST_PAYMENT,
            'paymentMethod' => PaymentMethod::getValidatedPaymentMethod($boost->getPaymentMethod()),
            'paymentAmountMillis' => (int) ($boost->getPaymentAmount() * 100 * 1000),
            'paymentTxId' => $boost->getPaymentTxId(),
        ]);

        $this->createPayment($paymentDetails);
    }

    /**
     * @param Wire $wire
     * @param string $paymentTxId
     * @param bool $isPlus
     * @param bool $isPro
     * @return void
     * @throws InvalidPaymentMethodException
     * @throws ServerErrorException
     */
    public function createPaymentFromWire(
        Wire $wire,
        string $paymentTxId,
        bool $isPlus = false,
        bool $isPro = false
    ): void {
        $request = $this->getServerRequest();
        $affiliateUser = isset($request->getCookieParams()['referrer']) ? $this->entitiesBuilder->getByUserByIndex($request->getCookieParams()['referrer']) : null;
        $affiliateUserGuid = (int) $affiliateUser?->getGuid() ?? null;
        if (!$affiliateUserGuid) {
            $affiliateUserGuid = (int) (
                $wire->getSender()->referrer && (time() - $wire->getSender()->time_created) < 365 * 86400
                ? (int) $wire->getSender()->referrer
                : null
            );
        }

        $paymentType = PaymentType::WIRE_PAYMENT;
        if ($isPlus) {
            $paymentType = PaymentType::MINDS_PLUS_PAYMENT;
        }
        if ($isPro) {
            $paymentType = PaymentType::MINDS_PRO_PAYMENT;
        }


        $paymentDetails = new PaymentDetails([
            'userGuid' => (int) $wire->getSender()->getGuid(),
            'affiliateUserGuid' => $affiliateUserGuid,
            'paymentType' => $paymentType,
            'paymentMethod' => PaymentMethod::getValidatedPaymentMethod(PaymentMethod::CASH),
            'paymentAmountMillis' => $wire->getAmount() * 100 * 1000,
            'paymentTxId' => $paymentTxId,
        ]);

        $this->createPayment($paymentDetails);
    }

    /**
     * @return ServerRequestInterface
     */
    private function getServerRequest(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals();
    }
}
