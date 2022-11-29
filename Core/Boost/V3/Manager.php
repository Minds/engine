<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Iterator;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentCaptureFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Guid;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;
use Stripe\Exception\ApiErrorException;

class Manager
{
    private User $user;

    public function __construct(
        private ?Repository $repository = null,
        private ?PaymentProcessor $paymentProcessor = null
    ) {
        $this->repository ??= Di::_()->get('Boost\V3\Repository');
        $this->paymentProcessor ??= new PaymentProcessor();
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param array $data
     * @return bool
     * @throws BoostPaymentSetupFailedException
     * @throws Exception
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function createBoost(array $data): bool
    {
        $this->repository->beginTransaction();

        $boost = (new Boost($data))
            ->setGuid(Guid::build());

        try {
            if (!$this->paymentProcessor->setupBoostPayment($boost)) {
                throw new BoostPaymentSetupFailedException();
            }

            if (!$this->repository->createBoost($boost)) {
                throw new ServerErrorException("An error occurred whilst creating the boost request");
            }
        } catch (Exception $e) {
            $this->repository->rollbackTransaction();
            throw $e;
        }

        $this->repository->commitTransaction();
        return true;
    }

    /**
     * @param string $boostGuid
     * @return bool
     * @throws Exception
     * @throws Exceptions\BoostNotFoundException
     * @throws InvalidBoostPaymentMethodException
     * @throws NotImplementedException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     * @throws ApiErrorException
     */
    public function approveBoost(string $boostGuid): bool
    {
        $this->repository->beginTransaction();

        try {
            $boost = $this->repository->getBoostByGuid($boostGuid);

            if (!$this->paymentProcessor->captureBoostPayment($boost)) {
                throw new BoostPaymentCaptureFailedException();
            }

            if (!$this->repository->approveBoost($boostGuid)) {
                throw new ServerErrorException();
            }
        } catch (Exception $e) {
            $this->repository->rollbackTransaction();

            // TODO: Ask Mark if we should set request status to failed

            throw $e;
        }

        $this->repository->commitTransaction();
        return true;
    }

    /**
     * @param string $boostGuid
     * @return bool
     * @throws ApiErrorException
     * @throws BoostPaymentCaptureFailedException
     * @throws Exception
     * @throws Exceptions\BoostNotFoundException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function rejectBoost(string $boostGuid): bool
    {
        $this->repository->beginTransaction();

        try {
            $boost = $this->repository->getBoostByGuid($boostGuid);

            if (!$this->paymentProcessor->refundBoostPayment($boost)) {
                throw new BoostPaymentCaptureFailedException();
            }

            // NOTE: By this point, even if the following business logic fails, the payment intent is already refunded
            // and cannot be captured so the boost should be marked as failed if an error occurs in the below code.
            // We might decide to handle the offchain token boost request in a different way
            // for the scenario mentioned above.

            if (!$this->repository->rejectBoost($boostGuid)) {
                throw new ServerErrorException();
            }
        } catch (Exception $e) {
            $this->repository->rollbackTransaction();
            throw $e;
        }

        $this->repository->commitTransaction();
        return true;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param int|null $targetStatus
     * @param bool $forApprovalQueue
     * @param string|null $targetUserGuid
     * @param bool $orderByRanking
     * @return Iterator
     */
    public function getBoosts(
        int $limit = 12,
        int $offset = 0,
        ?int $targetStatus = null,
        bool $forApprovalQueue = false,
        ?string $targetUserGuid = null,
        bool $orderByRanking = false
    ): Iterator {
        return $this->repository->getBoosts(
            limit: $limit,
            offset: $offset,
            targetStatus: $targetStatus,
            forApprovalQueue: $forApprovalQueue,
            targetUserGuid: $targetUserGuid ?? $this->user->getGuid(),
            orderByRanking: $orderByRanking
        );
    }
}
