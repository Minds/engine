<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Client;

class GraphQLMutationRequest extends GraphQLRequest
{
    public function __construct(
        public readonly string  $mutation = '',
        public readonly array   $variables = [],
        public readonly ?string $operationName = null,
    ) {
        parent::__construct($mutation, $variables, $operationName);
    }

    public function setMutation(string $mutation): self
    {
        return new self($mutation, $this->variables, $this->operationName);
    }
}
