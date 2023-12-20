<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Client;

class GraphQLQueryRequest extends GraphQLRequest
{
    public function __construct(
        public readonly string  $query = '',
        public readonly array   $variables = [],
        public readonly ?string $operationName = null,
    ) {
        parent::__construct($this->query, $this->variables, $this->operationName);
    }

    public function setQuery(string $query): self
    {
        return new self($query, $this->variables, $this->operationName);
    }
}
