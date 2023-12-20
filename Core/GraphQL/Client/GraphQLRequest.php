<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Client;

class GraphQLRequest
{
    public function __construct(
        private readonly string  $body = '',
        private readonly array   $variables = [],
        private readonly ?string $operationName = null,
    ) {
    }

    public function setVariables(array $variables): static
    {
        return new static($this->body, $variables, $this->operationName);
    }

    public function setOperationName(?string $operationName): static
    {
        return new static($this->body, $this->variables, $operationName);
    }
}
