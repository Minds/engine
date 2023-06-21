<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * A users onboarding state.
 */
#[Type]
class OnboardingState
{
    public function __construct(
        #[Field(outputType: 'String')] public readonly int $userGuid,
        #[Field] public readonly int $startedAt,
        #[Field] public readonly ?int $completedAt
    ) {
    }

    /**
     * Whether onboarding is completed.
     * @return bool whether onboarding is completed.
     */
    #[Field]
    public function isCompleted(): bool
    {
        return (bool) $this->completedAt;
    }
}
