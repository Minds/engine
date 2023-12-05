<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Exception;
use Iterator;
use Minds\Common\Repository\Response;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Data\Call;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Recurring\SupermindBulkIncentive\SupermindBulkIncentive;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as StripePaymentMethodsManager;
use Minds\Core\Payments\V2\Exceptions\InvalidPaymentMethodException;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Core\Supermind\Delegates\EventsDelegate;
use Minds\Core\Supermind\Delegates\TwitterEventsDelegate;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Exceptions\SupermindOffchainPaymentFailedException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentCaptureFailedException;
use Minds\Core\Supermind\Exceptions\SupermindPaymentIntentFailedException;
use Minds\Core\Supermind\Exceptions\SupermindRequestAcceptCompletionException;
use Minds\Core\Supermind\Exceptions\SupermindRequestCreationCompletionException;
use Minds\Core\Supermind\Exceptions\SupermindRequestDeleteException;
use Minds\Core\Supermind\Exceptions\SupermindRequestExpiredException;
use Minds\Core\Supermind\Exceptions\SupermindRequestIncorrectStatusException;
use Minds\Core\Supermind\Exceptions\SupermindRequestStatusUpdateException;
use Minds\Core\Supermind\Exceptions\SupermindUnauthorizedSenderException;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Payments\SupermindPaymentProcessor;
use Minds\Core\Twitter\Exceptions\TwitterDetailsNotFoundException;
use Minds\Core\Wire\Exceptions\RemoteUserException;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\StopEventException;
use Minds\Exceptions\UserCashSetupException;
use Minds\Exceptions\UserErrorException;
use Minds\Exceptions\UserNotFoundException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;

/**
 *
 */
class Manager
{
    private ?User $user = null;

    public function __construct(
        private ?Repository $repository = null,
        private ?SupermindPaymentProcessor $paymentProcessor = null,
        private ?EventsDelegate $eventsDelegate = null,
        private ?ACL $acl = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?TwitterEventsDelegate $twitterEventsDelegate = null,
        private ?Logger $logger = null
    ) {
        $this->repository ??= Di::_()->get("Supermind\Repository");
        $this->paymentProcessor ??= new SupermindPaymentProcessor();
        $this->eventsDelegate ??= new EventsDelegate();
        $this->acl ??= Di::_()->get('Security\ACL');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->twitterEventsDelegate ??= new TwitterEventsDelegate();
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->paymentProcessor->setUser($user);
        return $this;
    }

    /**
     * @param SupermindRequest $supermindRequest
     * @param string|null $paymentMethodId
     * @return bool
     * @throws ApiErrorException
     * @throws ForbiddenException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ServerErrorException
     * @throws SupermindOffchainPaymentFailedException
     * @throws SupermindPaymentIntentFailedException
     * @throws UnverifiedEmailException
     */
    public function addSupermindRequest(SupermindRequest $supermindRequest, ?string $paymentMethodId = null): bool
    {
        $receiver = $this->buildUser($supermindRequest->getReceiverGuid());

        if (
            !$this->acl->interact(
                $receiver,
                $this->user
            )
        ) {
            throw new ForbiddenException();
        }

        if ($receiver->getSource() !== FederatedEntitySourcesEnum::LOCAL) {
            throw new RemoteUserException();
        }
    
        $this->repository->beginTransaction();

        try {
            if ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH) {
                $paymentIntentId = $this->setupCashPayment($paymentMethodId, $supermindRequest);
                $supermindRequest->setPaymentTxID($paymentIntentId);
            } else {
                $this->paymentProcessor->setupOffchainPayment($supermindRequest);
            }

            $isRequestAdded = $this->repository->addSupermindRequest($supermindRequest);

            if (!$isRequestAdded) {
                if ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH) {
                    $this->paymentProcessor->cancelPaymentIntent($supermindRequest->getPaymentTxID());
                } else {
                    $this->paymentProcessor->refundOffchainPayment($supermindRequest);
                }

                $this->repository->rollbackTransaction();
            }
        } catch (CardException $e) {
            throw new SupermindPaymentIntentFailedException(message: $e->getMessage());
        } catch (ApiErrorException $e) {
            $receiver = $this->buildUser($supermindRequest->getReceiverGuid());
            throw new SupermindPaymentIntentFailedException(message: "@" . $receiver->getUsername() . " is unable to receive payments at this time");
        } catch (OffchainWalletInsufficientFundsException $e) {
            throw new SupermindOffchainPaymentFailedException(message: $e->getMessage());
        } catch (Exception $e) {
            $this->repository->rollbackTransaction();
            throw $e;
        }

        $this->repository->commitTransaction();
        return true;
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws ForbiddenException
     * @throws LockFailedException
     * @throws ServerErrorException
     * @throws StopEventException
     * @throws SupermindNotFoundException
     * @throws SupermindPaymentIntentCaptureFailedException
     * @throws SupermindRequestExpiredException
     * @throws SupermindRequestIncorrectStatusException
     * @throws UnverifiedEmailException
     * @throws UserCashSetupException
     * @throws UserErrorException
     */
    public function acceptSupermindRequest(string $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getStatus() !== SupermindRequestStatus::CREATED->value) {
            throw new SupermindRequestIncorrectStatusException();
        }

        if ($supermindRequest->getPaymentMethod() === SupermindRequestPaymentMethod::CASH && !isset($this->user->getMerchant()['id'])) {
            throw new UserCashSetupException();
        }

        if ($supermindRequest->isExpired()) {
            $this->expireSupermindRequest($supermindRequestId);
            throw new SupermindRequestExpiredException();
        }

        if (!$this->acl->write($supermindRequest, $this->user, ['isReply' => true])) {
            throw new ForbiddenException();
        }

        $isTransferFailed = false;
        if ($supermindRequest->getPaymentMethod() === SupermindRequestPaymentMethod::CASH) {
            try {
                $isPaymentSuccessful = $this->paymentProcessor->capturePaymentIntent($supermindRequest->getPaymentTxID());
            } catch (StripeTransferFailedException $e) {
                $isPaymentSuccessful = false;
                $isTransferFailed = true;
            }
        } else {
            $isPaymentSuccessful = $this->paymentProcessor->creditOffchainPayment($supermindRequest);
        }

        if ($isTransferFailed) {
            $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::TRANSFER_FAILED, $supermindRequestId);
        } elseif (!$isPaymentSuccessful) {
            $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::FAILED_PAYMENT, $supermindRequestId);
            throw new SupermindPaymentIntentCaptureFailedException();
        } else {
            $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::ACCEPTED, $supermindRequestId);
        }

        return true;
    }

    /**
     * @param string $supermindRequestId
     * @param SupermindRequestStatus $targetStatus
     * @return bool
     * @throws ServerErrorException
     * @throws SupermindRequestStatusUpdateException
     */
    public function updateSupermindRequestStatus(string $supermindRequestId, SupermindRequestStatus $targetStatus): bool
    {
        return $this->repository->updateSupermindRequestStatus($targetStatus, $supermindRequestId) ? true : throw new SupermindRequestStatusUpdateException();
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
            $paymentIntentId = $this->paymentProcessor->setupSupermindStripePayment($paymentMethodId, $supermindRequest);

            if (!$paymentIntentId) {
                $this->repository->rollbackTransaction();
                throw new SupermindPaymentIntentFailedException();
            }

            return $paymentIntentId;
        } catch (Exception $e) {
            $this->repository->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws SupermindNotFoundException
     * @throws SupermindRequestExpiredException
     * @throws SupermindRequestIncorrectStatusException
     * @throws SupermindUnauthorizedSenderException
     */
    public function revokeSupermindRequest(string $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getStatus() !== SupermindRequestStatus::CREATED->value) {
            throw new SupermindRequestIncorrectStatusException();
        }

        if ($supermindRequest->isExpired()) {
            $this->expireSupermindRequest($supermindRequestId);
            throw new SupermindRequestExpiredException();
        }

        if ($this->user->isAdmin() || $supermindRequest->getSenderGuid() !== $this->user->getGuid()) {
            throw new SupermindUnauthorizedSenderException();
        }

        $this->reimburseSupermindPayment($supermindRequest);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REVOKED, $supermindRequestId);

        return true;
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws ForbiddenException
     * @throws LockFailedException
     * @throws StopEventException
     * @throws SupermindNotFoundException
     * @throws SupermindRequestExpiredException
     * @throws SupermindRequestIncorrectStatusException
     * @throws SupermindUnauthorizedSenderException
     * @throws UnverifiedEmailException
     */
    public function rejectSupermindRequest(string $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        if ($supermindRequest->getStatus() !== SupermindRequestStatus::CREATED->value) {
            throw new SupermindRequestIncorrectStatusException();
        }

        if ($supermindRequest->isExpired()) {
            $this->expireSupermindRequest($supermindRequestId);
            throw new SupermindRequestExpiredException();
        }

        if ($supermindRequest->getReceiverGuid() !== $this->user->getGuid()) {
            throw new SupermindUnauthorizedSenderException();
        }

        if (!$this->acl->write($supermindRequest, $this->user, ['isReply' => true])) {
            throw new ForbiddenException();
        }

        $this->reimburseSupermindPayment($supermindRequest);

        $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::REJECTED, $supermindRequestId);

        $this->eventsDelegate->onRejectSupermindRequest($supermindRequest);

        return true;
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws SupermindNotFoundException
     */
    private function expireSupermindRequest(string $supermindRequestId): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        if (is_null($supermindRequest)) {
            throw new SupermindNotFoundException();
        }

        return $this->expireSupermindRequestFromDetails($supermindRequest);
    }

    /**
     * @param SupermindRequest $supermindRequest
     * @return bool
     * @throws ApiErrorException
     * @throws LockFailedException
     */
    private function expireSupermindRequestFromDetails(SupermindRequest $supermindRequest): bool
    {
        $this->reimburseSupermindPayment($supermindRequest);

        $expired = $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $supermindRequest->getGuid());

        $this->eventsDelegate->onExpireSupermindRequest($supermindRequest);

        return $expired;
    }

    /**
     * @param SupermindRequest $request
     * @return void
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws KeyNotSetupException
     */
    public function reimburseSupermindPayment(SupermindRequest $request): void
    {
        if ($request->getPaymentMethod() === SupermindRequestPaymentMethod::CASH) {
            $this->paymentProcessor->cancelPaymentIntent($request->getPaymentTxID());
        } else {
            $transactionID = $this->paymentProcessor->refundOffchainPayment($request);
            $this->repository->saveSupermindRefundTransaction($request->getGuid(), $transactionID);
        }
    }

    /**
     * @param string $supermindRequestId
     * @param int $activityGuid
     * @return bool
     * @throws SupermindRequestCreationCompletionException
     */
    public function completeSupermindRequestCreation(string $supermindRequestId, int $activityGuid, bool $triggerDelegatedEvents = true): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        $isSuccessful = $this->repository->updateSupermindRequestActivityGuid($supermindRequestId, $activityGuid);

        if ($isSuccessful) {
            $supermindRequest->setActivityGuid((string) $activityGuid);
            $supermindRequest->setEntity($this->entitiesBuilder->single($supermindRequest->getActivityGuid()));
            $supermindRequest->setReceiverEntity($this->entitiesBuilder->single($supermindRequest->getReceiverGuid()));
            if ($triggerDelegatedEvents) {
                $this->eventsDelegate->onCompleteSupermindRequestCreation($supermindRequest);
            }
        }

        return $isSuccessful
            ? true
            : throw new SupermindRequestCreationCompletionException();
    }

    /**
     * @param string $supermindRequestId
     * @param int $replyActivityGuid
     * @return bool
     * @throws SupermindRequestAcceptCompletionException
     * @throws TwitterDetailsNotFoundException
     */
    public function completeAcceptSupermindRequest(string $supermindRequestId, int $replyActivityGuid): bool
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId);

        $isSuccessful = $this->repository->updateSupermindRequestReplyActivityGuid($supermindRequestId, $replyActivityGuid);

        if ($isSuccessful) {
            $supermindRequest->setReplyActivityGuid((string) $replyActivityGuid);
            $supermindRequest->setEntity($this->entitiesBuilder->single($supermindRequest->getActivityGuid()));
            $supermindRequest->setReceiverEntity($this->entitiesBuilder->single($supermindRequest->getReceiverGuid()));
            $this->eventsDelegate->onAcceptSupermindRequest($supermindRequest);
            $this->twitterEventsDelegate->onAcceptSupermindOffer($supermindRequest);
        }

        return $isSuccessful
            ? true
            : throw new SupermindRequestAcceptCompletionException();
    }

    /**
     * @param string $supermindRequestId
     * @return bool
     * @throws SupermindRequestDeleteException
     */
    public function deleteSupermindRequest(string $supermindRequestId): bool
    {
        $isSuccessful = $this->repository->deleteSupermindRequest($supermindRequestId);

        return $isSuccessful
            ? true
            : throw new SupermindRequestDeleteException();
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param SupermindRequestStatus|null $status
     * @return Response
     */
    public function getReceivedRequests(int $offset, int $limit, ?SupermindRequestStatus $status): Response
    {
        $requests = [];
        foreach ($this->repository->getReceivedRequests(
            receiverGuid: (string) $this->user->getGuid(),
            offset: $offset,
            limit: $limit,
            status: $status
        ) as $supermindRequest) {
            $requests[] = $supermindRequest;
        }

        return new Response($requests);
    }

    /**
     * Count received requests for instance user.
     * @param SupermindRequestStatus|null $status - status to count for (null will return all).
     * @return integer returns count of received requests.
     */
    public function countReceivedRequests(?SupermindRequestStatus $status = null): int
    {
        return $this->repository->countReceivedRequests(
            receiverGuid: (string) $this->user->getGuid(),
            status: $status
        ) ?? 0;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param SupermindRequestStatus|null $status
     * @return Response
     */
    public function getSentRequests(int $offset, int $limit, ?SupermindRequestStatus $status): Response
    {
        $requests = [];
        foreach ($this->repository->getSentRequests(
            senderGuid: (string) $this->user->getGuid(),
            offset: $offset,
            limit: $limit,
            status: $status
        ) as $supermindRequest) {
            $requests[] = $supermindRequest;
        }

        return new Response($requests);
    }

    /**
     * Count sent requests for instance user.
     * @param SupermindRequestStatus|null $status - status to count for (null will return all).
     * @return integer returns count of sent requests.
     */
    public function countSentRequests(?SupermindRequestStatus $status = null): int
    {
        return $this->repository->countSentRequests(
            senderGuid: (string) $this->user->getGuid(),
            status: $status
        ) ?? 0;
    }

    /**
     * @param string $supermindRequestId
     * @return SupermindRequest
     * @throws ForbiddenException
     * @throws SupermindNotFoundException
     */
    public function getRequest(string $supermindRequestId): SupermindRequest
    {
        $supermindRequest = $this->repository->getSupermindRequest($supermindRequestId) ?? throw new SupermindNotFoundException();

        if (!$this->acl->read($supermindRequest, $this->user)) {
            throw new ForbiddenException("Unable to view this Supermind - Are you logged into the correct account?");
        }

        $supermindRequest->setEntity($this->entitiesBuilder->single($supermindRequest->getActivityGuid()));
        $supermindRequest->setReceiverEntity($this->entitiesBuilder->single($supermindRequest->getReceiverGuid()));

        return $supermindRequest;
    }

    public function getSupermindRequestsFromIds(array $supermindRequestIDs): Iterator
    {
        foreach ($this->repository->getRequestsFromIds($supermindRequestIDs) as $supermindRequest) {
            yield $supermindRequest;
        }
    }

    /**
     * @return bool
     * @throws ForbiddenException
     * @throws Exception
     */
    public function expireRequests(): bool
    {
        if (php_sapi_name() !== "cli") {
            throw new ForbiddenException();
        }

        ini_set('display_errors', '1');
        error_reporting(E_ALL);

        foreach ($this->repository->getExpiredRequests(SupermindRequest::SUPERMIND_EXPIRY_THRESHOLD) as $request) {
            $this->logger->info('Expiring Supermind', [$request->getGuid()]);

            $this->logger->info('Setting supermind request status to expiring in progress', ['request_guid' => $request->getGuid()]);
            $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRING_IN_PROGRESS, $request->getGuid());

            $this->logger->info('Status updated to ' . SupermindRequestStatus::EXPIRING_IN_PROGRESS->name, ['request_guid' => $request->getGuid()]);

            /**
             * Transaction started after updating status to expiring in progress
             * to prevent refund being processed multiple times due to errors
             */
            $this->logger->info('Beginning transaction', ['request_guid' => $request->getGuid()]);
            $this->repository->beginTransaction();
            try {
                // Refund offchain token payment
                if ($request->getPaymentMethod() === SupermindRequestPaymentMethod::OFFCHAIN_TOKEN) {
                    $this->logger->info('Supermind request payment method is offchain tokens', ['request_guid' => $request->getGuid()]);

                    // Refund offchain payment
                    $this->logger->info('Refunding supermind request', ['request_guid' => $request->getGuid()]);
                    $transactionId = $this->paymentProcessor->refundOffchainPayment($request);
                    $this->logger->info('Supermind request refunded', ['request_guid' => $request->getGuid()]);

                    // Store refund transaction
                    $this->logger->info('Storing supermind refund transaction', [
                        'request_guid' => $request->getGuid(),
                        'transaction_id' => $transactionId
                    ]);
                    $this->repository->saveSupermindRefundTransaction($request->getGuid(), $transactionId);
                    $this->logger->info('Supermind refund transaction stored', [
                        'request_guid' => $request->getGuid(),
                        'transaction_id' => $transactionId
                    ]);
                }

                // Update supermind request status to Expired and commit transaction
                $this->logger->info('Setting supermind request status to expired', ['request_guid' => $request->getGuid()]);
                $this->repository->updateSupermindRequestStatus(SupermindRequestStatus::EXPIRED, $request->getGuid());
                $this->repository->commitTransaction();

                $this->logger->info('Status updated to ' . SupermindRequestStatus::EXPIRED->name, ['request_guid' => $request->getGuid()]);
                $this->logger->info('Transaction committed', ['request_guid' => $request->getGuid()]);

                $this->logger->info('Firing to events delegate for Supermind', [$request->getGuid()]);
                $this->eventsDelegate->onExpireSupermindRequest($request);
            } catch (Exception $e) {
                $this->logger->error('Error expiring supermind', [
                    'request_guid' => $request->getGuid(),
                    'exception' => [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTrace()
                    ]
                ]);
                $this->repository->rollbackTransaction();
                continue;
            }
        }

        $this->logger->info('Finished expiring superminds');

        return true;
    }

    /**
     * @param string $userGuid
     * @return User
     * @throws ServerErrorException
     */
    private function buildUser(string $userGuid): User
    {
        return $this->entitiesBuilder->single($userGuid) ?? throw new ServerErrorException();
    }

    /**
     * @param int $status
     * @return Iterator
     */
    public function getSupermindRequestsByStatus(int $status): Iterator
    {
        foreach ($this->repository->getRequestsByStatus($status) as $supermindRequest) {
            yield $supermindRequest;
        }
    }

    public function isSupermindRequestRefunded(string $supermindRequestId): bool
    {
        return (bool) $this->repository->getSupermindRefundTransactionId($supermindRequestId);
    }

    /**
     * @param $bulkSupermindRequestDetails
     * @return bool
     * @throws ApiErrorException
     * @throws ForbiddenException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ServerErrorException
     * @throws SupermindOffchainPaymentFailedException
     * @throws SupermindPaymentIntentFailedException
     * @throws SupermindRequestCreationCompletionException
     * @throws UserNotFoundException
     * @throws UnverifiedEmailException
     */
    public function createBulkSupermindRequest($bulkSupermindRequestDetails): bool
    {
        $receiverUser = $this->entitiesBuilder->single($bulkSupermindRequestDetails['receiver_guid']);
        if (!$receiverUser instanceof User) {
            throw new UserNotFoundException("User " . $bulkSupermindRequestDetails['receiver_guid'] . " not found");
        }

        $activityGuid = $bulkSupermindRequestDetails['source_activity'] ?? '';
        $replyType = (int) $bulkSupermindRequestDetails['reply_type'] ?? SupermindRequestReplyType::TEXT;
        $paymentMethod = (int) $bulkSupermindRequestDetails['payment_type'] ?? SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $paymentAmount = (float) $bulkSupermindRequestDetails['payment_amount'] ?? 5;

        $validatorToken = $this->getBulkSupermindRequestValidatorToken(
            $receiverUser,
            $activityGuid,
            $replyType,
            $paymentMethod,
            $paymentAmount
        );

        if (!$this->checkAndStoreUserOfferClaim((string) $receiverUser->getGuid(), $validatorToken)) {
            throw new Exception("Offer already claimed"); // user has already claimed the offer
        }

        $activity = $this->entitiesBuilder->single($activityGuid);

        /** @var User $activityOwner */
        $activityOwner =  $this->entitiesBuilder->single($activity->getOwnerGuid());

        $supermindRequest = (new SupermindRequest())
            ->setGuid(Guid::build())
            ->setSenderGuid((string) $activityOwner->getGuid())
            ->setReceiverGuid((string) $receiverUser->getGuid())
            ->setReplyType($replyType)
            ->setTwitterRequired(false)
            ->setPaymentAmount($paymentAmount)
            ->setPaymentMethod($paymentMethod);

        $paymentMethodId = null;

        if ($paymentMethod == SupermindRequestPaymentMethod::CASH) {
            $paymentMethods = (new StripePaymentMethodsManager())->getList([ 'user_guid' => $activityOwner->getGuid() ]);
            if (count($paymentMethods) === 0) {
                throw new InvalidPaymentMethodException("No valid payment methods were found for user {$activityOwner->getGuid()}");
            }
            $paymentMethodId = $paymentMethods[0]->getId();
        }

        $this->setUser($activityOwner);
        $this->addSupermindRequest($supermindRequest, $paymentMethodId);

        $this->completeSupermindRequestCreation($supermindRequest->getGuid(), (int) $activityGuid, false);

        return true;
    }

    /**
     * @param string $receiverGuid
     * @param string $activityGuid
     * @param int $replyType
     * @param int $paymentMethod
     * @param int $paymentAmount
     * @return string
     * @throws ServerErrorException
     */
    private function getBulkSupermindRequestValidatorToken(
        User $receiverUser,
        string $activityGuid,
        int $replyType,
        int $paymentMethod,
        float $paymentAmount
    ): string {
        return (new SupermindBulkIncentive())
            ->setUser($receiverUser)
            ->withActivityGuid($activityGuid)
            ->withReplyType($replyType)
            ->withPaymentMethod($paymentMethod)
            ->withPaymentAmount($paymentAmount)
            ->getValidatorToken();
    }

    /**
     * Returns false if the user has already claimed the offer, true otherwise
     * @param string $receiverGuid
     * @param string $validatorToken
     * @return bool
     */
    private function checkAndStoreUserOfferClaim(string $receiverGuid, string $validatorToken): bool
    {
        $db = new Call('entities_by_time');
        $row = $db->getRow("analytics:rewarded:email:$validatorToken", [
            'offset' => $receiverGuid,
            'limit' => 1
        ]);

        if (isset($row[$receiverGuid])) {
            return false; // Don't proceed further as the user has claimed the supermind offer
        }

        // Save the ref so we don't allow to proceed past this point on next run
        $db->insert("analytics:rewarded:email:$validatorToken", [ $receiverGuid => time() ]);
        return true;
    }
}
