<?php
namespace Minds\Core\GraphQL\Traits;

trait GraphQLSubQueryTrait
{
    private object $gqlQueryRef;

    public function setQueryRef(object $controller): self
    {
        $this->gqlQueryRef = $controller;
        return $this;
    }
}
