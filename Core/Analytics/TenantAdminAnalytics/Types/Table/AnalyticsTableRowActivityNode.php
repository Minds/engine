<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types\Table;

use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowNodeInterface;
use Minds\Core\Feeds\GraphQL\Types\ActivityNode;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class AnalyticsTableRowActivityNode implements AnalyticsTableRowNodeInterface
{
    public function __construct(
        #[Field] public readonly ActivityNode $activity,
        #[Field] public readonly int $views,
        #[Field] public readonly int $engagements,
    ) {
        
    }

    #[Field]
    public function getId(): ID
    {
        return new ID('analytics-' . $this->activity->getGuid());
    }

}
