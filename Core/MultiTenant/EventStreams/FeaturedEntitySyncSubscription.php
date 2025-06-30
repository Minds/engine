<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\EventStreams;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Services\FeaturedEntityAutoSubscribeService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use Minds\Entities\Entity;

/**
 * Subscription to sync users and groups to featured entities.
 */
class FeaturedEntitySyncSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?TenantUsersService $multiTenantUsersService = null,
        private ?FeaturedEntityAutoSubscribeService $featuredEntityAutoSubscribeService = null,
        private ?Logger $logger = null,
        private ?Config $config = null
    ) {
        $this->multiTenantUsersService ??= Di::_()->get(TenantUsersService::class);
        $this->featuredEntityAutoSubscribeService ??= Di::_()->get(FeaturedEntityAutoSubscribeService::class);
        $this->logger ??= Di::_()->get('Logger');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Returns subscription id.
     * @return string subscription id.
     */
    public function getSubscriptionId(): string
    {
        return 'featured-entity-sync';
    }

    /**
     * Returns topic.
     * @return ActionEventsTopic - topic.
     */
    public function getTopic(): ActionEventsTopic
    {
        return new ActionEventsTopic();
    }

    /**
     * Returns topic regex, scoping subscription to the event that we want to subscribe to.
     * @return string topic regex.
     */
    public function getTopicRegex(): string
    {
        return ActionEvent::ACTION_FEATURED_ENTITY_ADDED;
    }

    /**
     * Called on event receipt.
     * @param EventInterface $event - The event to consume.
     * @return bool - Whether the event was consumed.
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $this->logger->info('Consuming event: ' . $event->getAction());

        $entity = $event->getEntity();
        $featuredEntityData = (array) $event->getActionData()['featured_entity_data'];

        if (
            !$entity ||
            $event->getAction() !== ActionEvent::ACTION_FEATURED_ENTITY_ADDED
        ) {
            return true;
        }

        $tenantId = $this->config->get('tenant_id');

        if (!$tenantId || $tenantId < 1) {
            $this->logger->error("No tenant id set");
            return true;
        }

        return match($entity->getType()) {
            'user' => $this->syncFeaturedUserAddition(
                $this->hydrateFeaturedUser($featuredEntityData),
                $tenantId
            ),
            'group' => $this->syncFeaturedGroupAddition(
                $this->hydrateFeaturedGroup($featuredEntityData),
                $tenantId
            ),
            default => $this->handleUnsupportedEntityType($entity),
        };
    }

    /**
     * Syncs a featured user addition.
     * @param FeaturedUser $featuredUser - The featured user to sync users with.
     * @param int $tenantId - The tenant ID.
     * @return bool - Whether the event was consumed.
     */
    private function syncFeaturedUserAddition(FeaturedUser $featuredUser, int $tenantId): bool
    {
        $this->logger->info('Syncing new featured user ' . $featuredUser->entityGuid);

        foreach ($this->multiTenantUsersService->getUsers(tenantId: $tenantId) as $user) {
            try {
                if ((int) $user->getGuid() === $featuredUser->entityGuid) {
                    $this->logger->info('Skipping self-subscription for user: ' . $user->getGuid());
                    continue;
                }

                $this->featuredEntityAutoSubscribeService->handleFeaturedUser($featuredUser, $user);
                $this->logger->info('Handled user: ' . $user->getGuid() . ' for featured user: ' . $featuredUser->entityGuid);
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        return true;
    }

    /**
     * Syncs a featured group addition.
     * @param FeaturedGroup $featuredGroup - The featured group to sync users with.
     * @param int $tenantId - The tenant ID.
     * @return bool - Whether the event was consumed.
     */
    private function syncFeaturedGroupAddition(FeaturedGroup $featuredGroup, int $tenantId): bool
    {
        $this->logger->info('Syncing new featured group ' . $featuredGroup->entityGuid);

        foreach ($this->multiTenantUsersService->getUsers(tenantId: $tenantId) as $user) {
            try {
                $this->featuredEntityAutoSubscribeService->handleFeaturedGroup($featuredGroup, $user);
                $this->logger->info('Handled user: ' . $user->getGuid() . ' for featured group: ' . $featuredGroup->entityGuid);
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }
        return true;
    }

    /**
     * Hydrates a featured user from data array.
     * @param array $data - The data array.
     * @return FeaturedUser - The featured user.
     */
    private function hydrateFeaturedUser(array $data): FeaturedUser
    {
        return new FeaturedUser(
            tenantId: $data['tenantId'],
            entityGuid: $data['entityGuid'],
            autoSubscribe: $data['autoSubscribe'] ?? false,
            recommended: $data['recommended'] ?? false,
            autoPostSubscription: $data['autoPostSubscription'] ?? null,
            username: $data['username'] ?? null,
            name: $data['name'] ?? null
        );
    }

    /**
     * Hydrates a featured group from data array.
     * @param array $data - The data array.
     * @return FeaturedGroup - The featured group.
     */
    private function hydrateFeaturedGroup(array $data): FeaturedGroup
    {
        return new FeaturedGroup(
            tenantId: $data['tenantId'],
            entityGuid: $data['entityGuid'],
            autoSubscribe: $data['autoSubscribe'] ?? false,
            recommended: $data['recommended'] ?? false,
            autoPostSubscription: $data['autoPostSubscription'] ?? null,
            name: $data['name'] ?? null,
            briefDescription: $data['briefDescription'] ?? null,
            membersCount: $data['membersCount'] ?? null
        );
    }

    /**
     * Handles an unsupported entity type.
     * @param Entity $entity - The entity to handle.
     * @return bool - Whether the event was consumed.
     */
    private function handleUnsupportedEntityType(Entity $entity): bool
    {
        $this->logger->error("Unsupported entity of type: " . $entity->getType());
        return true;
    }
}
