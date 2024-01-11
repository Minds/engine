<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Factories;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Types\FeaturedEntity;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use TheCodingMachine\GraphQLite\Annotations\Factory;

/**
 * Factory for FeaturedEntity.
 */
class FeaturedEntityFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Creates FeaturedEntity.
     * @param string $entityGuid - guid of entity.
     * @param bool $autoSubscribe - whether entity is auto-subscribed.
     * @param bool $recommended - whether entity is recommended.
     * @return FeaturedEntity - featured entity.
     */
    #[Factory(name: 'FeaturedEntityInput')]
    public function createFeaturedEntity(
        string $entityGuid,
        bool $autoSubscribe = false,
        bool $autoPostSubscription = false,
        bool $recommended = false
    ): FeaturedEntity {
        return new FeaturedUser(
            tenantId: $this->config->get('tenant_id'),
            entityGuid: (int) $entityGuid,
            autoSubscribe: $autoSubscribe,
            autoPostSubscription: $autoPostSubscription,
            recommended: $recommended
        );
    }
}
