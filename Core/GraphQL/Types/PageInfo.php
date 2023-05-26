<?php
namespace Minds\Core\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class PageInfo
{
    public function __construct(
        private bool $hasNextPage,
        private bool $hasPreviousPage,
        private ?string $startCursor,
        private ?string $endCursor
    ) {
    }

    #[Field]
    public function getHasNextPage(): bool
    {
        return $this->hasNextPage;
    }

    #[Field]
    public function getHasPreviousPage(): bool
    {
        return $this->hasPreviousPage;
    }

    #[Field]
    public function getStartCursor(): ?string
    {
        return $this->startCursor;
    }

    #[Field]
    public function getEndCursor(): ?string
    {
        return $this->endCursor;
    }
}
