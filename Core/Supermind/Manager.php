<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentFailedException;
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
     * @param string $paymentMethodId
     * @param SupermindRequest $supermindRequest
     * @return bool
     * @throws SupermindPaymentIntentFailedException
     * @throws Exception
     */
    public function addSupermindRequest(string $paymentMethodId, SupermindRequest $supermindRequest): bool
    {
        $this->repository->beginTransaction();

        if ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH) {
            $supermindRequest->setPaymentTxID(
                $this->setupCashPayment($paymentMethodId, $supermindRequest)
            );
        } else {
            $this->paymentProcessor->setupSupermindOffchainPayment($supermindRequest);
        }

        try {
            $isRequestAdded = $this->repository->addSupermindRequest($supermindRequest);

            if (!$isRequestAdded) {
                $this->paymentProcessor->cancelPaymentIntent($supermindRequest->getPaymentTxID());
                $this->repository->rollbackTransaction();
            }
        } catch (Exception $e) {
            throw $e;
        }

        $this->repository->commitTransaction();
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
     * @param string $supermindRequestId
     * @return bool
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     * @throws ApiErrorException
     */
    public function revokeSupermindRequest(string $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getSenderGuid() !== $this->user->getGuid()) {
            throw new SupermindUnauthorizedSenderException();
        }

        $this->reimburseSupermindPayment(
            $supermindRequest->getPaymentMethod(),
            $supermindRequest->getPaymentTxID()
        );

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REVOKED, $supermindRequestId);

        return true;
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws SupermindNotFoundException
     * @throws SupermindUnauthorizedSenderException
     */
    public function rejectSupermindRequest(string $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getReceiverGuid() !== $this->user->getGuid()) {
            throw new SupermindUnauthorizedSenderException();
        }

        $this->reimburseSupermindPayment(
            $supermindRequest->getPaymentMethod(),
            $supermindRequest->getPaymentTxID()
        );

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REJECTED, $supermindRequestId);
        return true;
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws ForbiddenException
     * @throws SupermindNotFoundException
     */
    public function expireSupermindRequest(string $supermindRequestId): bool
    {
        if (php_sapi_name() !== "cli") {
            throw new ForbiddenException();
        }

        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        $this->reimburseSupermindPayment(
            $supermindRequest->getPaymentMethod(),
            $supermindRequest->getPaymentTxID()
        );

        return $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequestId);
    }

    /**
     * @param int $paymentMethod
     * @param string $paymentTxId
     * @return void
     * @throws ApiErrorException
     */
    private function reimburseSupermindPayment(int $paymentMethod, string $paymentTxId): void
    {
        if ($paymentMethod === SupermindRequestPaymentMethod::CASH) {
            $this->paymentProcessor->cancelPaymentIntent($paymentTxId);
        } else {
            $this->paymentProcessor->refundOffchainPayment();
        }
    }

    /**
     * @return Response
     * @throws SupermindNotFoundException
     */
    public function getRequests(): Response
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
}
