<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use Minds\Core\Payments\Lago\Enums\SubscriptionBillingTimeEnum;
use Minds\Core\Payments\Lago\Enums\SubscriptionStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Subscription
{
    /**
     * @param SubscriptionBillingTimeEnum $billingTime
     * @param int $mindsCustomerId
     * @param string $mindsSubscriptionId
     * @param string $planCodeId
     * @param SubscriptionStatusEnum $status
     * @param string $name
     * @param int|null $startedAt
     * @param int|null $endingAt
     */
    public function __construct(
        #[Field] public readonly SubscriptionBillingTimeEnum $billingTime,
        #[Field] public readonly int $mindsCustomerId,
        #[Field] public readonly string $mindsSubscriptionId,
        #[Field] public readonly string $planCodeId,
        #[Field] public readonly SubscriptionStatusEnum $status,
        #[Field] public readonly string $name = "",
        #[Field] public readonly ?int $startedAt = null,
        #[Field] public readonly ?int $endingAt = null,
    ) {
    }
}
