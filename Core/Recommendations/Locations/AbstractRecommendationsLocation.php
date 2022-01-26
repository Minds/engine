<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Entities\User;

abstract class AbstractRecommendationsLocation implements LocationInterface
{
    protected ?User $user;

    public function setUser(?User $user): LocationInterface
    {
        $this->user = $user;
        return $this;
    }
}
