<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Client;

/**
 * Immutable GraphQL request.
 */
class GraphQLRequest
{
    public function __construct(
        public readonly string  $body = '',
        public readonly array   $variables = [],
        public readonly ?string $operationName = null,
    ) {
    }

    /**
     * @param string $body
     * @return self
     */
    public function setBody(string $body): self
    {
        return new self($body, $this->variables, $this->operationName);
    }

    /**
     * @param array $variables
     * @return self
     */
    public function setVariables(array $variables): self
    {
        return new self($this->body, $variables, $this->operationName);
    }

    /**
     * @param string|null $operationName
     * @return self
     */
    public function setOperationName(?string $operationName): self
    {
        return new self($this->body, $this->variables, $operationName);
    }
}
