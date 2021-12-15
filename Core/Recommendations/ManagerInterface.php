<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;
use Minds\Entities\User;

interface ManagerInterface
{
    public function getRecommendations(?User $user, string $location): Response;
}
