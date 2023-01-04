<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentCaptureFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentRefundFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\EntityTypeNotAllowedInLocationException;
use Minds\Core\Boost\V3\Exceptions\IncorrectBoostStatusException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;
use Stripe\Exception\ApiErrorException;

class Manager
{
    private ?User $user = null;

    public function __construct(
        private ?Repository $repository = null,
        private ?PaymentProcessor $paymentProcessor = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->paymentProcessor ??= new PaymentProcessor();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
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
        if (!$this->isEntityTypeAllowed($data['entity_guid'], (int) $data['target_location'])) {
            throw new EntityTypeNotAllowedInLocationException();
        }

        $this->repository->beginTransaction();

        $boost = (
            new Boost(
                entityGuid: $data['entity_guid'],
                targetLocation: (int) $data['target_location'],
                targetSuitability: (int) $data['target_suitability'],
                paymentMethod: (int) $data['payment_method'],
                paymentAmount: (float) ($data['daily_bid'] * $data['duration_days']),
                dailyBid: (float) $data['daily_bid'],
                durationDays: (int) $data['duration_days'],
            )
        )
            ->setGuid(Guid::build())
            ->setOwnerGuid($this->user->getGuid())
            ->setPaymentMethodId($data['payment_method_id'] ?? null);

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
     * Checks if the provided entity can be boosted.
     * @param string $entityGuid
     * @param int $targetLocation
     * @return bool
     */
    private function isEntityTypeAllowed(string $entityGuid, int $targetLocation): bool
    {
        $entity = $this->entitiesBuilder->single($entityGuid);

        if (!$entity) {
            return false;
        }

        return match ($entity->getType()) {
            'activity' => $targetLocation === BoostTargetLocation::NEWSFEED,
            'user' => $targetLocation === BoostTargetLocation::SIDEBAR,
            default => false
        };
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

            throw $e;
        }

        $this->repository->commitTransaction();
        return true;
    }

    /**
     * @param string $boostGuid
     * @return bool
     * @throws ApiErrorException
     * @throws BoostPaymentRefundFailedException
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
        // Only process if status is Pending
        $boost = $this->repository->getBoostByGuid($boostGuid);

        if ($boost->getStatus() !== BoostStatus::PENDING) {
            throw new IncorrectBoostStatusException();
        }

        // Mark request as Refund_in_progress
        $this->repository->updateStatus($boostGuid, BoostStatus::REFUND_IN_PROGRESS);

        if (!$this->paymentProcessor->refundBoostPayment($boost)) {
            throw new BoostPaymentRefundFailedException();
        }

        // Mark request as Refund_processed
        $this->repository->updateStatus($boostGuid, BoostStatus::REFUND_PROCESSED);

        if (!$this->repository->rejectBoost($boostGuid)) {
            throw new ServerErrorException();
        }

        return true;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param int|null $targetStatus
     * @param bool $forApprovalQueue
     * @param string|null $targetUserGuid
     * @param bool $orderByRanking
     * @param int $targetAudience
     * @param int|null $targetLocation
     * @param string|null $entityGuid
     * @return Response
     */
    public function getBoosts(
        int $limit = 12,
        int $offset = 0,
        ?int $targetStatus = null,
        bool $forApprovalQueue = false,
        ?string $targetUserGuid = null,
        bool $orderByRanking = false,
        int $targetAudience = BoostTargetAudiences::SAFE,
        ?int $targetLocation = null,
        ?string $entityGuid = null
    ): Response {
        $hasNext = false;
        $boosts = $this->repository->getBoosts(
            limit: $limit,
            offset: $offset,
            targetStatus: $targetStatus,
            forApprovalQueue: $forApprovalQueue,
            targetUserGuid: $targetUserGuid,
            orderByRanking: $orderByRanking,
            targetAudience: $targetAudience,
            targetLocation: $targetLocation,
            entityGuid: $entityGuid,
            loggedInUser: $this->user,
            hasNext: $hasNext
        );

        return new Response(iterator_to_array($boosts), $hasNext);
    }

    /**
     * Get a single boost by its GUID.
     * @param string $boostGuid - guid to get boost for.
     * @return Boost|null - boost with matching GUID.
     */
    public function getBoostByGuid(string $boostGuid): ?Boost
    {
        try {
            return $this->repository->getBoostByGuid($boostGuid);
        } catch (BoostNotFoundException $e) {
            return null;
        }
    }
}
