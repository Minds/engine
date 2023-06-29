<?php
namespace Minds\Core\GraphQL\Traits;

use Minds\Entities\User;

trait GraphQLSubQueryTrait
{
    private object $gqlQueryRef;
    private ?User $loggedInUser;

    public function setQueryRef(object $controller, ?User $loggedInUser = null): self
    {
        $this->gqlQueryRef = $controller;
        $this->loggedInUser = $loggedInUser;
        return $this;
    }
}
