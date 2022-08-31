<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentFailedException;
use Minds\Core\Supermind\Exceptions\SupermindRequestCreationCompletionException;
use Minds\Core\Supermind\Exceptions\SupermindRequestDeleteException;
use Minds\Core\Supermind\Exceptions\SupermindUnauthorizedSenderException;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Payments\SupermindPaymentProcessor;
use Minds\Entities\User;
use Stripe\Exception\ApiErrorException;

/**
 *
 */
class Manager
{
    private User $user;

    public function __construct(
        private ?Repository $repository = null,
        private ?SupermindPaymentProcessor $paymentProcessor = null
    ) {
        $this->repository ??= Di::_()->get("Supermind\Repository");
        $this->paymentProcessor ??= new SupermindPaymentProcessor();
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param SupermindRequest $supermindRequest
     * @param string|null $paymentMethodId
     * @return bool
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws SupermindPaymentIntentFailedException
     */
    public function addSupermindRequest(SupermindRequest $supermindRequest, ?string $paymentMethodId = null): bool
    {
        $this->repository->beginTransaction();

        if ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH) {
            $paymentIntentId = $this->setupCashPayment($paymentMethodId, $supermindRequest);
            $supermindRequest->setPaymentTxID($paymentIntentId);
        } else {
            $this->paymentProcessor->setupOffchainPayment($supermindRequest);
        }

        try {
            $isRequestAdded = $this->repository->addSupermindRequest($supermindRequest);

            if (!$isRequestAdded) {
                if ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH) {
                    $this->paymentProcessor->cancelPaymentIntent($supermindRequest->getPaymentTxID());
                } else {
                    $this->paymentProcessor->refundOffchainPayment($supermindRequest);
                }

                $this->repository->rollbackTransaction();
            }
        } catch (Exception $e) {
            throw $e;
        }

        $this->repository->commitTransaction();
        return true;
    }

    /**
     * @param int $supermindRequestId
     * @param int $status
     * @return bool
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     */
    public function acceptSupermindRequest(int $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getPaymentMethod() === SupermindRequestPaymentMethod::CASH) {
            $isPaymentSuccessful = $this->paymentProcessor->capturePaymentIntent($supermindRequest->getPaymentTxID());
        } else {
            $isPaymentSuccessful = $this->paymentProcessor->creditOffchainPayment($supermindRequest);
        }

        if (!$isPaymentSuccessful) {
            $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::FAILED_PAYMENT, $supermindRequestId);
            // TODO: Complete validation
        } else {
            $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::ACCEPTED, $supermindRequestId);
        }

        return true;
    }

    /**
     * @param string $paymentMethodId
     * @param SupermindRequest $supermindRequest
     * @return string
     * @throws SupermindPaymentIntentFailedException
     * @throws Exception
     */
    private function setupCashPayment(string $paymentMethodId, SupermindRequest $supermindRequest): string
    {
        try {
            $paymentIntent = $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest);

            if (!$paymentIntent->getId()) {
                $this->repository->rollbackTransaction();
                throw new SupermindPaymentIntentFailedException();
            }

            return $paymentIntent->getId();
        } catch (Exception $e) {
            $this->repository->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param int $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     */
    public function revokeSupermindRequest(int $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getSenderGuid() !== $this->user->getGuid()) {
            throw new SupermindUnauthorizedSenderException();
        }

        $this->reimburseSupermindPayment($supermindRequest);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REVOKED, $supermindRequestId);

        return true;
    }

    /**
     * @param int $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     */
    public function rejectSupermindRequest(int $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getReceiverGuid() !== $this->user->getGuid()) {
            throw new SupermindUnauthorizedSenderException();
        }

        $this->reimburseSupermindPayment($supermindRequest);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REJECTED, $supermindRequestId);
        return true;
    }

    /**
     * @param int $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws ForbiddenException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     */
    public function expireSupermindRequest(int $supermindRequestId): bool
    {
        if (php_sapi_name() !== "cli") {
            throw new ForbiddenException();
        }

        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        $this->reimburseSupermindPayment($supermindRequest);

        return $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequestId);
    }

    /**
     * @param SupermindRequest $request
     * @return void
     * @throws ApiErrorException
     * @throws LockFailedException
     */
    private function reimburseSupermindPayment(SupermindRequest $request): void
    {
        if ($request->getPaymentMethod() === SupermindRequestPaymentMethod::CASH) {
            $this->paymentProcessor->cancelPaymentIntent($request->getPaymentTxID());
        } else {
            $this->paymentProcessor->refundOffchainPayment($request);
        }
    }

    /**
     * @param int $supermindRequestId
     * @param int $activityGuid
     * @return bool
     * @throws SupermindRequestCreationCompletionException
     */
    public function completeSupermindRequestCreation(int $supermindRequestId, int $activityGuid): bool
    {
        $isSuccessful = $this->repository->updateSupermindRequestActivityGuid($supermindRequestId, $activityGuid);

        return $isSuccessful
            ? true
            : throw new SupermindRequestCreationCompletionException();
    }

    /**
     * @param int $supermindRequestId
     * @return bool
     * @throws SupermindRequestDeleteException
     */
    public function deleteSupermindRequest(int $supermindRequestId): bool
    {
        $isSuccessful = $this->repository->deleteSupermindRequest($supermindRequestId);

        return $isSuccessful
            ? true
            : throw new SupermindRequestDeleteException();
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return Response
     * @throws SupermindNotFoundException
     */
    public function getRequests(int $offset, int $limit): Response
    {
        $requests = [];
        foreach ($this->repository->getReceivedRequests($this->user->getGuid()) as $supermindRequest) {
            $requests[] = $supermindRequest;
        }

        if (count($requests) === 0) {
            throw new SupermindNotFoundException();
        }

        return new Response($requests);
    }

    /**
     * @param int $supermindRequestId
     * @return SupermindRequest
     */
    public function getRequest(int $supermindRequestId): SupermindRequest
    {
        return $this->repository->getSupermindRequest($supermindRequestId);
    }
}
