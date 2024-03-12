<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\Di\Di;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use Minds\Core\Groups\Membership as GroupMembership;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Entities\Group;

/**
 * Featured group node. Can be used in a connection.
 */
#[Type]
class FeaturedGroup extends FeaturedEntity
{
    public function __construct(
        #[Field(outputType: 'String!')] public readonly int $tenantId,
        #[Field(outputType: 'String!')] public readonly int $entityGuid,
        #[Field] public readonly bool $autoSubscribe,
        #[Field] public readonly bool $recommended,
        #[Field] public readonly bool $autoPostSubscription = false,
        private readonly ?string $name = null,
        private readonly ?int $membersCount = null,
    ) {
    }

    /**
     * Gets group name.
     * @return string group name.
     */
    #[Field]
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Gets count of members.
     * @return int count of members.
     */
    #[Field]
    public function getMembersCount(): int
    {
        /** @var Manager */
        $manager = Di::_()->get(Manager::class);
        return $manager->getMembersCount((new Group())->set('guid', $this->entityGuid)) ?? 0;
    }
}
