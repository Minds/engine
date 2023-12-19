<?php
namespace Minds\Core\Comments\EmbeddedComments\Models;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class EmbeddedCommentsSettings
{
    public function __construct(
        #[Field] public readonly int $userGuid,
        #[Field] public readonly string $domain,
        #[Field] public readonly string $pathRegex,
        #[Field] public readonly bool $autoImportsEnabled,
    ) {
        
    }
}
