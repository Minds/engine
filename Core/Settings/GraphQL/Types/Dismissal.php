<?php
namespace Minds\Core\Settings\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Holds information on a users Dismissals (e.g. explainer screens).
 */
#[Type()]
class Dismissal
{
    public function __construct(
        #[Field] public readonly string $userGuid,
        #[Field] public readonly string $key,
        #[Field] public int $dismissalTimestamp
    ) {
    }
}
