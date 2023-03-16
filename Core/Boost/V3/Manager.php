<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Analytics\Views\View;
use Minds\Core\Analytics\Views\Manager as ViewsManager;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Boost\Checksum;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
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
use Minds\Core\Boost\V3\Models\BoostEntityWrapper;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Security\ACL;
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
        private ?ActionEventDelegate $actionEventDelegate = null,
        private ?PreApprovalManager $preApprovalManager = null,
        private ?ViewsManager $viewsManager = null,
        private ?ACL $acl = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->paymentProcessor ??= new PaymentProcessor();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->actionEventDelegate ??= Di::_()->get(ActionEventDelegate::class);
        $this->preApprovalManager ??= Di::_()->get(PreApprovalManager::class);
        $this->viewsManager ??= new ViewsManager();
        $this->acl ??= new ACL();
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
     * @throws EntityTypeNotAllowedInLocationException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws ServerErrorException
     * @throws OffchainWalletInsufficientFundsException
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
            $isOnchainBoost = $boost->getPaymentMethod() === BoostPaymentMethod::ONCHAIN_TOKENS;

            if (!$isOnchainBoost && $this->preApprovalManager->shouldPreApprove($this->user)) {
                $this->preApprove($boost);
                return true;
            }

            if ($isOnchainBoost) {
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

        $this->actionEventDelegate->onCreate($boost);

        return true;
    }

    /**
     * Takes a boost ready for creation and pre-approves it.
     * @param Boost $boost - boost to pre-approve.
     * @throws BoostPaymentSetupFailedException
     * @throws BoostPaymentCaptureFailedException
     * @throws ServerErrorException
     * @return void
     */
    private function preApprove(Boost $boost): void
    {
        $presetTimestamp = strtotime(date('c', time()));
        $boost->setStatus(BoostStatus::APPROVED)
            ->setCreatedTimestamp($presetTimestamp)
            ->setUpdatedTimestamp($presetTimestamp)
            ->setApprovedTimestamp($presetTimestamp);

        if (!$this->paymentProcessor->setupBoostPayment($boost)) {
            throw new BoostPaymentSetupFailedException();
        }

        if (!$this->paymentProcessor->captureBoostPayment($boost)) {
            throw new BoostPaymentCaptureFailedException();
        }

        if (!$this->repository->createBoost($boost)) {
            throw new ServerErrorException("An error occurred whilst creating the boost request");
        }
        
        $this->repository->commitTransaction();

        $this->actionEventDelegate->onCreate($boost);
        $this->actionEventDelegate->onApprove($boost);
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
     * @param string|null $adminGuid
     * @return bool
     * @throws ApiErrorException
     * @throws BoostNotFoundException
     * @throws BoostPaymentCaptureFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    public function approveBoost(string $boostGuid, string $adminGuid = null): bool
    {
        $this->repository->beginTransaction();

        try {
            $boost = $this->repository->getBoostByGuid($boostGuid);

            if (!$this->paymentProcessor->captureBoostPayment($boost)) {
                throw new BoostPaymentCaptureFailedException();
            }

            if (!$this->repository->approveBoost(
                boostGuid: $boostGuid,
                adminGuid: $adminGuid
            )) {
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
     * @param int $reasonCode
     * @return bool
     * @throws ApiErrorException
     * @throws BoostNotFoundException
     * @throws BoostPaymentRefundFailedException
     * @throws IncorrectBoostStatusException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function rejectBoost(string $boostGuid, int $reasonCode): bool
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

        if (!$this->repository->rejectBoost($boostGuid, $reasonCode)) {
            throw new ServerErrorException();
        }

        $this->actionEventDelegate->onReject($boost, $reasonCode);

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
     * @param int|null $targetAudience
     * @param int|null $targetLocation
     * @param string|null $entityGuid
     * @param int|null $paymentMethod
     * @return Response
     */
    public function getBoosts(
        int $limit = 12,
        int $offset = 0,
        ?int $targetStatus = null,
        bool $forApprovalQueue = false,
        ?string $targetUserGuid = null,
        bool $orderByRanking = false,
        ?int $targetAudience = null,
        ?int $targetLocation = null,
        ?string $entityGuid = null,
        ?int $paymentMethod = null
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
            paymentMethod: $paymentMethod,
            loggedInUser: $this->user,
            hasNext: $hasNext
        );

        $boostsArray = iterator_to_array($boosts);

        foreach ($boostsArray as $i => $boost) {
            if ($boost->getEntity() && !$this->acl->read($boost)) {
                unset($boostsArray[$i]);
            }
        }

        return new Response($boostsArray, $hasNext);
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
     * @param int|null $targetLocation
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

        $boostsArray = iterator_to_array($boosts);

        foreach ($boostsArray as $i => $boost) {
            if ($boost->getEntity() && !$this->acl->read($boost)) {
                unset($boostsArray[$i]);
                continue;
            }
            if (((int) $targetLocation === BoostTargetLocation::SIDEBAR) && $boost->getEntity()) {
                $this->recordSidebarView($boost, $i);
            }
        }

        $feedSyncEntities = $this->castToFeedSyncEntities($boostsArray);

        return new Response($feedSyncEntities, $hasNext);
    }

    /**
     * Get a single boost by its GUID.
     * @param string $boostGuid - guid to get boost for.
     * @return Boost|null - boost with matching GUID.
     */
    public function getBoostByGuid(string $boostGuid): ?Boost
    {
        try {
            $boost = $this->repository->getBoostByGuid($boostGuid);
            if (!$boost || !$this->acl->read($boost)) {
                return null;
            }
            return $boost;
        } catch (BoostNotFoundException $e) {
            return null;
        }
    }

    /**
     * Force reject boosts in given statuses, by entity guid.
     * @param string $entityGuid - entity guid for which to force boost status.
     * @param int $reason - reason to be set as reject reason on update.
     * @param array $statuses - array of statuses to update status for.
     * @return bool true on success.
     */
    public function forceRejectByEntityGuid(
        string $entityGuid,
        int $reason,
        array $statuses = [BoostStatus::APPROVED, BoostStatus::PENDING]
    ): bool {
        return $this->repository->forceRejectByEntityGuid(
            entityGuid: $entityGuid,
            reason: $reason,
            statuses: $statuses,
        );
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
     * @throws ServerErrorException
     * @throws UserErrorException
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
     * Get admin stats from repository.
     * @return Response admin stats as response.
     */
    public function getAdminStats(): Response
    {
        $globalPendingStats = $this->repository->getAdminStats(
            targetStatus: BoostStatus::PENDING
        );

        return new Response([
            'global_pending' => [
                'safe_count' => (int) $globalPendingStats['safe_count'],
                'controversial_count' => (int) $globalPendingStats['controversial_count']
            ]
        ]);
    }

    /**
     * A temporary solution for being able to rank the sidebar boosts
     * @param Boost $boost
     * @return void
     */
    public function recordSidebarView(Boost $boost, int $position): void
    {
        $this->viewsManager->record(
            (new View())
                ->setEntityUrn($boost->getEntity()->getUrn())
                ->setOwnerGuid((string) $boost->getEntity()->getOwnerGuid())
                ->setClientMeta([
                    'source' => 'feed/subscribed', // TODO: this should be the actual source
                    'medium' => 'sidebar',
                    'campaign' => $boost->getUrn(),
                    'position' => $position,
                ]),
            $boost->getEntity(),
        );
    }

    /**
     * Casts an array of boosts to feed sync entities from boost,
     * containing the exported boosted content.
     * @param Boost[] $boosts - boosts to cast
     * @return array feed sync entities.
     */
    private function castToFeedSyncEntities(array $boosts): array
    {
        $feedSyncEntities = [];

        foreach ($boosts as $boost) {
            $feedSyncEntities[] = (new FeedSyncEntity())
                ->setGuid($boost->getGuid())
                ->setOwnerGuid($boost->getOwnerGuid())
                ->setTimestamp($boost->getCreatedTimestamp())
                ->setUrn($boost->getUrn())
                ->setEntity(
                    $boost->getEntity() ?
                    new BoostEntityWrapper($boost) :
                    null
                );
        }

        return $feedSyncEntities;
    }
}
