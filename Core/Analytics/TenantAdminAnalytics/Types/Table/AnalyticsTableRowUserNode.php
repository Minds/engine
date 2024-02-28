<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types\Table;

use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowNodeInterface;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class AnalyticsTableRowUserNode implements AnalyticsTableRowNodeInterface
{
    public function __construct(
        #[Field] public readonly UserNode $user,
        #[Field] public readonly int $newSubscribers,
        #[Field] public readonly int $totalSubscribers,
        // #[Field] public readonly int $engagements,
    ) {
        
    }

    #[Field]
    public function getId(): ID
    {
        return new ID('analytics-' . $this->user->getGuid());
    }

}
