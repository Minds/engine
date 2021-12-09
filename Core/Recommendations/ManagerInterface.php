<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;

interface ManagerInterface
{
    public function getRecommendations(string $location): Response;
}
