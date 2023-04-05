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
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

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
        $affiliateUser = isset($_COOKIE['referrer']) ? $this->entitiesBuilder->getByUserByIndex($_COOKIE['referrer']) : null;
        $affiliateUserGuid = (int) $affiliateUser?->getGuid() ?? null;
        if (!$affiliateUserGuid && $this->user->getGuid() === $boost->getOwnerGuid()) {
            $affiliateUserGuid =
                $this->user->referrer && (time() - $this->user->time_created) < 365 * 86400
                    ? $this->user->referrer
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
}
