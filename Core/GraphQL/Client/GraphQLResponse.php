<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Client;

use Minds\Core\GraphQL\Client\Enums\GraphQLRequestStatusEnum;

class GraphQLResponse
{
    public function __construct(
        public readonly GraphQLRequestStatusEnum $status,
        private readonly array                   $data,
        private readonly array                   $errors = [],
    ) {
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toObject(): object
    {
        return (object)$this->data;
    }

    public function __toString(): string
    {
        return serialize($this);
    }
}
