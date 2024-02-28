<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types\Table;

use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowNodeInterface;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class AnalyticsTableRowGroupNode implements AnalyticsTableRowNodeInterface
{
    public function __construct(
        #[Field] public readonly GroupNode $group,
        #[Field] public readonly int $newMembers,
        // #[Field] public readonly int $totalMembers,
        // #[Field] public readonly int $engagements,
    ) {
        
    }

    #[Field]
    public function getId(): ID
    {
        return new ID('analytics-' . $this->group->getGuid());
    }

}
