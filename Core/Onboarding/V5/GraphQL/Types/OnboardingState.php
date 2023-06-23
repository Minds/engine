<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5\GraphQL\Types;

use Minds\Entities\ExportableInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * A users onboarding state.
 */
#[Type]
class OnboardingState implements ExportableInterface
{
    public function __construct(
        #[Field(outputType: 'String')] public readonly int $userGuid,
        #[Field] public readonly int $startedAt,
        #[Field] public readonly ?int $completedAt
    ) {
    }

    /**
     * Export the object to an array.
     * @param array $extras - extras.
     * @return array exported object.
     */
    public function export(array $extras = []): array
    {
        return [
            'user_guid' => $this->userGuid,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            ...$extras
        ];
    }
}
