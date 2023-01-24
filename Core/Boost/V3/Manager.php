<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Boost\Checksum;
use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
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
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;
use Stripe\Exception\ApiErrorException;

class Manager
{
    private ?User $user = null;

    private ?Logger $logger = null;

    public function __construct(
        private ?Repository $repository = null,
        private ?PaymentProcessor $paymentProcessor = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ActionEventDelegate $actionEventDelegate = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->paymentProcessor ??= new PaymentProcessor();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->actionEventDelegate ??= Di::_()->get(ActionEventDelegate::class);

        $this->logger = Di::_()->get("Logger");
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
            ->setGuid($data['guid'] ?? Guid::build())
            ->setOwnerGuid($this->user->getGuid())
            ->setPaymentMethodId($data['payment_method_id'] ?? null);

        try {
            if ($boost->getPaymentMethod() === BoostPaymentMethod::ONCHAIN_TOKENS) {
                $boost->setStatus(BoostStatus::PENDING_ONCHAIN_CONFIRMATION)
                    ->setPaymentTxId($data['payment_tx_id']);
            } elseif (!$this->paymentProcessor->setupBoostPayment($boost)) {
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

        $this->actionEventDelegate->onApprove($boost);

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

        // TODO: Get rejection reason from boost when possible.
        $this->actionEventDelegate->onReject($boost, 999);

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
    public function cancelBoost(string $boostGuid): bool
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

        if (!$this->repository->cancelBoost($boostGuid, $this->user->getGuid())) {
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
        ?int $paymentMethod = null,
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
            paymentMethod: $paymentMethod,
            entityGuid: $entityGuid,
            loggedInUser: $this->user,
            hasNext: $hasNext
        );

        return new Response(iterator_to_array($boosts), $hasNext);
    }

    /**
     * Get boost feed as feed sync entities.
     * @param int $limit
     * @param int $offset
     * @param int|null $targetStatus
     * @param bool $forApprovalQueue
     * @param string|null $targetUserGuid
     * @param bool $orderByRanking
     * @param int $targetAudience
     * @return Response
     */
    public function getBoostFeed(
        int $limit = 12,
        int $offset = 0,
        ?int $targetStatus = null,
        bool $forApprovalQueue = false,
        ?string $targetUserGuid = null,
        bool $orderByRanking = false,
        int $targetAudience = BoostTargetAudiences::SAFE,
        ?int $targetLocation = null
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
            loggedInUser: $this->user,
            hasNext: $hasNext
        );
        $feedSyncEntities = $this->castToFeedSyncEntities($boosts);
        return new Response($feedSyncEntities);
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

    /**
     * Update the status of a single boost.
     * @param string $boostGuid - guid of boost to update.
     * @return bool true if boost updated.
     */
    public function updateStatus(string $boostGuid, int $status): bool
    {
        return $this->repository->updateStatus($boostGuid, $status);
    }

    /**
     * Will prepare an onchain boost
     * @param string $entityGuid
     * @return array
     */
    public function prepareOnchainBoost(string $entityGuid): array
    {
        $entity = $this->entitiesBuilder->single($entityGuid);

        if (!($entity instanceof Activity || $entity instanceof User)) {
            throw new ServerErrorException("Invalid entity type provided");
        }

        if ($entity->getNsfw() || $entity->getNsfwLock()) {
            throw new UserErrorException('NSFW content cannot be boosted.');
        }

        $guid = Guid::build();
        $checksum = (new Checksum())
            ->setGuid($guid)
            ->setEntity($entity)
            ->generate();

        return [
            'guid' => $guid,
            'checksum' => $checksum
        ];
    }

    public function processExpiredApprovedBoosts(): void
    {
        $this->repository->beginTransaction();

        foreach ($this->repository->getExpiredApprovedBoosts() as $boost) {
            $this->repository->updateStatus($boost->getGuid(), BoostStatus::COMPLETED);

            $this->actionEventDelegate->onComplete($boost);

            echo "\n";
            $this->logger->addInfo("Boost {$boost->getGuid()} has been marked as COMPLETED");
            echo "\n";
        }

        $this->repository->commitTransaction();
    }

    /**
     * Casts an array of boosts to feed sync entities from boost,
     * containing the exported boosted content.
     * @param iterable $boosts - boosts to cast
     * @return array feed sync entities.
     */
    private function castToFeedSyncEntities(iterable $boosts): array
    {
        $feedSyncEntities = [];

        foreach ($boosts as $boost) {
            $exportedBoostEntity = $boost->export()['entity'];
            $exportedBoostEntity['boosted'] = true;
            $exportedBoostEntity['boosted_guid'] = $boost->getGuid();
            $exportedBoostEntity['urn'] = $boost->getUrn();

            $feedSyncEntities[] = (new FeedSyncEntity())
                ->setGuid($boost->getGuid())
                ->setOwnerGuid($boost->getOwnerGuid())
                ->setTimestamp($boost->getCreatedTimestamp())
                ->setUrn($boost->getUrn())
                ->setExportedEntity($exportedBoostEntity);
        }

        return $feedSyncEntities;
    }
}
