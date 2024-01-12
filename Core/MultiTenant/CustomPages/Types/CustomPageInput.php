<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\CustomPages\Types;

use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * Multi-tenant custom page input model.
 */
#[Input]
class CustomPageInput
{
    public function __construct(
        #[Field] public readonly int $pageType,
        #[Field] public readonly ?string $content = null,
        #[Field] public readonly ?string $externalLink = null,
    ) {
    }
}
