<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;
use Minds\Entities\User;

interface ManagerInterface
{
    /**
     * Retrieves the recommendations based on the location provided
     * @param User|null $user
     * @param string $location
     * @param array|null $options
     * @return Response
     */
    public function getRecommendations(?User $user, string $location, ?array $options = []): Response;
}
