<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Analytics\Views\Manager as ViewsManager;
use Minds\Core\Analytics\Views\View;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Boost\Checksum;
use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Exceptions\BoostCashPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostCreationFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentCaptureFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentRefundFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\EntityTypeNotAllowedInLocationException;
use Minds\Core\Boost\V3\Exceptions\IncorrectBoostStatusException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Models\BoostEntityWrapper;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Entities\GuidLinkResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardInsufficientFundsException;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardNotFoundException;
use Minds\Core\Payments\InAppPurchases\Apple\AppleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Enums\InAppPurchasePaymentMethodIdsEnum;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Manager as InAppPurchasesManager;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\V2\Exceptions\InvalidPaymentMethodException;
use Minds\Core\Security\ACL;
use Minds\Core\Settings\Manager as UserSettingsManager;
use Minds\Core\Settings\Models\BoostPartnerSuitability;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
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
        private ?Repository          $repository = null,
        private ?PaymentProcessor    $paymentProcessor = null,
        private ?EntitiesBuilder     $entitiesBuilder = null,
        private ?ActionEventDelegate $actionEventDelegate = null,
        private ?PreApprovalManager  $preApprovalManager = null,
        private ?ViewsManager        $viewsManager = null,
        private ?ACL                 $acl = null,
        private ?GuidLinkResolver    $guidLinkResolver = null,
        private ?UserSettingsManager $userSettingsManager = null,
        private ?ExperimentsManager  $experimentsManager = null,
        private ?InAppPurchasesManager $inAppPurchasesManager = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->paymentProcessor ??= new PaymentProcessor();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->actionEventDelegate ??= Di::_()->get(ActionEventDelegate::class);
        $this->preApprovalManager ??= Di::_()->get(PreApprovalManager::class);
        $this->viewsManager ??= new ViewsManager();
        $this->acl ??= new ACL();
        $this->logger = Di::_()->get("Logger");
        $this->guidLinkResolver ??= Di::_()->get(GuidLinkResolver::class);
        $this->userSettingsManager ??= Di::_()->get('Settings\Manager');
        $this->experimentsManager ??= Di::_()->get('Experiments\Manager');
        $this->inAppPurchasesManager ??= Di::_()->get(InAppPurchasesManager::class);
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
     * @throws ApiErrorException
     * @throws BoostPaymentCaptureFailedException
     * @throws BoostPaymentSetupFailedException
     * @throws EntityTypeNotAllowedInLocationException
     * @throws InvalidBoostPaymentMethodException
     * @throws InvalidPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    public function createBoost(array $data): bool
    {
        $entity = $this->entitiesBuilder->single($data['entity_guid']);

        if ($entity && $entity->getType() !== 'activity' && $entity->getType() !== 'user') {
            $activity = $this->guidLinkResolver->resolveActivityFromEntityGuid($data['entity_guid']);
            if ($activity) {
                $data['entity_guid'] = $activity->getGuid();
                $entity = $activity;
            }
        }

        if (!$this->isEntityTypeAllowed($entity, (int) $data['target_location'])) {
            throw new EntityTypeNotAllowedInLocationException();
        }

        $goalFeatureEnabled = $this->experimentsManager
            ->isOn('minds-3952-boost-goals');

        $targetPlatformFeatureEnabled = $this->experimentsManager
            ->isOn('minds-4030-boost-platform-targeting');

        $boost = (
            new Boost(
                entityGuid: $data['entity_guid'],
                targetLocation: (int) $data['target_location'],
                targetSuitability: (int) $data['target_suitability'],
                paymentMethod: (int) $data['payment_method'],
                paymentAmount: (float) ($data['daily_bid'] * $data['duration_days']),
                dailyBid: (float) $data['daily_bid'],
                durationDays: (int) $data['duration_days'],
                goal: $goalFeatureEnabled && isset($data['goal']) ? (int) $data['goal'] : null,
                goalButtonText: $goalFeatureEnabled && isset($data['goal_button_text']) ? (int) $data['goal_button_text'] : null,
                goalButtonUrl: $goalFeatureEnabled && isset($data['goal_button_url']) ? (string) $data['goal_button_url'] : null,
                targetPlatformWeb: !($targetPlatformFeatureEnabled && isset($data['target_platform_web'])) || $data['target_platform_web'],
                targetPlatformAndroid: !($targetPlatformFeatureEnabled && isset($data['target_platform_android'])) || $data['target_platform_android'],
                targetPlatformIos: !($targetPlatformFeatureEnabled && isset($data['target_platform_ios'])) || $data['target_platform_ios'],
            )
        )
            ->setGuid($data['guid'] ?? Guid::build())
            ->setOwnerGuid($this->user->getGuid())
            ->setPaymentMethodId($data['payment_method_id'] ?? null);

        $this->processNewBoost($boost, $data['payment_tx_id'] ?? null, $data['iap_transaction'] ?? null);

        $this->actionEventDelegate->onCreate($boost);

        if ($boost->getStatus() === BoostStatus::APPROVED) {
            $this->actionEventDelegate->onApprove($boost);
        }

        return true;
    }

    /**
     * @param Boost $boost
     * @param bool $isOnchainBoost
     * @param string|null $paymentTxId
     * @return void
     * @throws BoostCashPaymentSetupFailedException
     * @throws BoostPaymentSetupFailedException
     * @throws GiftCardInsufficientFundsException
     * @throws InvalidBoostPaymentMethodException
     * @throws InvalidPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ServerErrorException
     * @throws GiftCardNotFoundException
     */
    private function processNewBoostPayment(
        Boost $boost,
        bool $isOnchainBoost,
        ?string $paymentTxId = null,
        ?string $iapTransaction = null
    ) : void {
        $purchaseProductDetails = null;
        if ($iapTransaction) {
            $iapTransactionDetails = json_decode($iapTransaction);

            $purchaseProductDetails = $this->inAppPurchasesManager->getProductPurchaseDetails(
                new InAppPurchase(
                    source: $boost->getPaymentMethodId() === InAppPurchasePaymentMethodIdsEnum::GOOGLE->value ? GoogleInAppPurchasesClient::class : AppleInAppPurchasesClient::class,
                    purchaseToken: $iapTransactionDetails->purchaseToken ?? "",
                    productId: $iapTransactionDetails->productId ?? "",
                    transactionId: $iapTransaction,
                )
            );
        }
        /**
         * Boost payment entry into `minds_payments` had to be separated into its own method
         * and outside the main transaction to avoid causing an issue with the foreign key
         * in the `minds_gift_card_transactions` table.
         */
        $paymentDetails = $this->paymentProcessor->createMindsPayment($boost, $this->user, $purchaseProductDetails);
        $boost->setPaymentGuid($paymentDetails->paymentGuid);

        $this->repository->beginTransaction();
        $this->paymentProcessor->beginTransaction();

        if ($isOnchainBoost) {
            $boost->setStatus(BoostStatus::PENDING_ONCHAIN_CONFIRMATION)
                ->setPaymentTxId($paymentTxId);
        } elseif (!$this->paymentProcessor->setupBoostPayment($boost, $this->user, $paymentDetails)) {
            throw new BoostPaymentSetupFailedException();
        }

        // Update minds payment with tx_id for non IAP purchases
    }

    /**
     * @param Boost $boost
     * @param string|null $paymentTxId
     * @return void
     * @throws ApiErrorException
     * @throws BoostCreationFailedException
     * @throws BoostPaymentCaptureFailedException
     * @throws BoostPaymentSetupFailedException
     * @throws BoostCashPaymentSetupFailedException
     * @throws GiftCardInsufficientFundsException
     * @throws InvalidBoostPaymentMethodException
     * @throws InvalidPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    private function processNewBoost(
        Boost $boost,
        ?string $paymentTxId = null,
        ?string $iapTransaction = null
    ): void {
        try {
            $isOnchainBoost = $boost->getPaymentMethod() === BoostPaymentMethod::ONCHAIN_TOKENS;

            if (!$isOnchainBoost && $this->preApprovalManager->shouldPreApprove($this->user)) {
                $this->preApprove($boost, $paymentTxId, $iapTransaction);
                return;
            }

            $this->processNewBoostPayment($boost, $isOnchainBoost, $paymentTxId, $iapTransaction);

            if (!$this->repository->createBoost($boost)) {
                throw new BoostCreationFailedException();
            }

            $this->repository->commitTransaction();
            $this->paymentProcessor->commitTransaction();
        } catch (BoostCreationFailedException|GiftCardInsufficientFundsException $e) {
            $this->paymentProcessor->refundBoostPayment($boost);
            $this->repository->rollbackTransaction();

            throw $e;
        } catch (Exception $e) {
            $this->repository->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Takes a boost ready for creation and pre-approves it.
     *
     * TODO: Refactor as it has unnecessary duplication with processNewBoost
     * @param Boost $boost - boost to pre-approve.
     * @return void
     * @throws ApiErrorException
     * @throws BoostCreationFailedException
     * @throws BoostPaymentCaptureFailedException
     * @throws BoostPaymentSetupFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws InvalidPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    private function preApprove(
        Boost $boost,
        ?string $paymentTxId = null,
        ?string $iapTransaction = null
    ): void {
        $presetTimestamp = strtotime(date('c', time()));
        $boost->setStatus(BoostStatus::APPROVED)
            ->setCreatedTimestamp($presetTimestamp)
            ->setUpdatedTimestamp($presetTimestamp)
            ->setApprovedTimestamp($presetTimestamp);

        $purchaseProductDetails = null;
        if ($iapTransaction) {
            $iapTransactionDetails = json_decode($iapTransaction);

            $purchaseProductDetails = $this->inAppPurchasesManager->getProductPurchaseDetails(
                new InAppPurchase(
                    source: $boost->getPaymentMethodId() === InAppPurchasePaymentMethodIdsEnum::GOOGLE->value ? GoogleInAppPurchasesClient::class : AppleInAppPurchasesClient::class,
                    purchaseToken: $iapTransactionDetails->purchaseToken ?? "",
                    productId: $iapTransactionDetails->productId ?? "",
                    transactionId: $iapTransaction,
                )
            );
        }

        $paymentDetails = $this->paymentProcessor->createMindsPayment($boost, $this->user, $purchaseProductDetails);

        $this->repository->beginTransaction();
        $this->paymentProcessor->beginTransaction();

        if (!$this->paymentProcessor->setupBoostPayment($boost, $this->user, $paymentDetails)) {
            throw new BoostPaymentSetupFailedException();
        }

        if (!$this->repository->createBoost($boost)) {
            throw new BoostCreationFailedException();
        }

        $this->repository->commitTransaction();
        $this->paymentProcessor->commitTransaction();

        /**
         * Needs to be after the transaction commits due to transaction lock being placed on
         * payments table because of foreign key constraint in gift card transactions table.
         */
        if (!$this->paymentProcessor->captureBoostPayment($boost)) {
            throw new BoostPaymentCaptureFailedException();
        }
    }

    /**
     * Checks if the provided entity can be boosted.
     * @param EntityInterface $entity
     * @param int $targetLocation
     * @return bool
     */
    private function isEntityTypeAllowed(EntityInterface $entity, int $targetLocation): bool
    {
        if (!$entity) {
            return false;
        }

        return match ($entity->getType()) {
            'activity' => $targetLocation === BoostTargetLocation::NEWSFEED,
            'user' => $targetLocation === BoostTargetLocation::SIDEBAR,
            'group' => $targetLocation === BoostTargetLocation::SIDEBAR,
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

            if ($boost->getStatus() !== BoostStatus::PENDING) {
                throw new IncorrectBoostStatusException();
            }

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
     * @param string|null $servedByGuid - guid of the user serving the boost.
     * @param string|null $source - source of the boost for client meta.
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
        ?int $targetLocation = null,
        ?string $servedByGuid = null,
        ?string $source = null,
        bool $castToFeedSyncEntities = true,
    ): Response {
        $hasNext = false;

        if ($servedByGuid) {
            $servedByTargetAudience = $this->getServedByTargetAudience($servedByGuid);

            // if no audience, return null.
            if (!$servedByTargetAudience) {
                return new Response([]);
            }

            // if the users target audience is fixed to safe, respect it.
            if ($servedByTargetAudience === BoostTargetAudiences::SAFE && $targetAudience !== BoostTargetAudiences::SAFE) {
                $targetAudience = BoostTargetAudiences::SAFE;
            }
        }

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
            if (!$boost->getEntity() || !$this->acl->read($boost)) {
                unset($boostsArray[$i]);
                continue;
            }
            if (((int) $targetLocation === BoostTargetLocation::SIDEBAR) && $boost->getEntity()) {
                $this->recordSidebarView($boost, $i, [
                    'source' => $source,
                    'served_by_guid' => $servedByGuid
                ]);
            }
        }

        if ($castToFeedSyncEntities) {
            $feedSyncEntities = $this->castToFeedSyncEntities($boostsArray);

            return new Response($feedSyncEntities, $hasNext);
        } else {
            return new Response($boostsArray, $hasNext);
        }
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
        $this->logger->warning("Processing expired approved boosts");
        foreach ($this->repository->getExpiredApprovedBoosts() as $boost) {
            $this->logger->warning("Processing expired boost {$boost->getGuid()}", $boost->export());
            $this->repository->updateStatus($boost->getGuid(), BoostStatus::COMPLETED);

            $this->actionEventDelegate->onComplete($boost);

            echo "\n";
            $this->logger->info("Boost {$boost->getGuid()} has been marked as COMPLETED");
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
     * @param Boost $boost - boost to record for.
     * @param int $position - position of the boost.
     * @param array $clientMeta - array to be merged with default client meta.
     * @return void
     */
    public function recordSidebarView(Boost $boost, int $position, array $clientMeta = []): void
    {
        $clientMeta = array_merge([
            'source' => 'feed/subscribed', // TODO: this should be overridden with the actual source - minds#3873
            'medium' => 'sidebar',
            'campaign' => $boost->getUrn(),
            'position' => $position,
        ], $clientMeta);

        $this->viewsManager->record(
            (new View())
                ->setEntityUrn($boost->getEntity()->getUrn())
                ->setOwnerGuid((string) $boost->getEntity()->getOwnerGuid())
                ->setClientMeta($clientMeta)
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

    /**
     * Gets target audience for user belonging to the "served by" guid.
     * @param string $servedByGuid - guid to get settings for.
     * @return int|null target audience for given served by guid.
     */
    private function getServedByTargetAudience(string $servedByGuid): ?int
    {
        $servedByUser = $this->entitiesBuilder->single($servedByGuid);
        if (!$servedByUser || !$servedByUser instanceof User) {
            return BoostTargetAudiences::CONTROVERSIAL;
        }

        $userSettings = $this->userSettingsManager->setUser($servedByUser)
            ->getUserSettings(allowEmpty: true);

        return match ($userSettings->getBoostPartnerSuitability()) {
            BoostPartnerSuitability::SAFE => BoostTargetAudiences::SAFE,
            BoostPartnerSuitability::CONTROVERSIAL => BoostTargetAudiences::CONTROVERSIAL,
            BoostPartnerSuitability::DISABLED => null,
            default => BoostTargetAudiences::CONTROVERSIAL
        };
    }

    /**
     * Whether boosts should be shown for a given user.
     * @param User $user - user to show.
     * @param integer $showBoostsAfterX - how long after registration till users should see boosts. Defaults to 1 weeks.
     * @return boolean true if boosts should be shown.
     */
    public function shouldShowBoosts(User $user, int $showBoostsAfterX = 604800): bool
    {
        /**
         * Do not show boosts if plus and disabled flag
         */
        if ($user->disabled_boost && $user->isPlus()) {
            return false;
        }

        return (time() - $user->getTimeCreated()) > $showBoostsAfterX;
    }
}
