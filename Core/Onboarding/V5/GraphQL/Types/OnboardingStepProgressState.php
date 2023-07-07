<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * A users progress for a specific onboarding step.
 */
#[Type]
class OnboardingStepProgressState
{
    public function __construct(
        #[Field(outputType: 'String')] public readonly int $userGuid,
        #[Field] public readonly string $stepKey,
        #[Field] public readonly string $stepType,
        #[Field] public readonly ?int $completedAt = null
    ) {
    }
}
