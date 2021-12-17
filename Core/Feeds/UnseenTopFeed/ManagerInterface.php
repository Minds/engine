<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Common\Repository\Response;

interface ManagerInterface
{
    public function getUnseenTopEntities(int $totalEntitiesToRetrieve): Response;
}
