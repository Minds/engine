<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\CustomPages\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\MultiTenant\CustomPages\Types\CustomPage;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * MultiTenant CustomPages edge
 * ojm not used
 */
#[Type]
class CustomPageEdge implements EdgeInterface
{
    public function __construct(
        private CustomPage $node,
        private readonly ?string $cursor = null
    ) {
    }

    #[Field]
    public function getNode(): ?CustomPage
    {
        return $this->node;
    }

     #[Field] public function getCursor(): string
    {
        return $this->cursor ?? "";
    }
}
