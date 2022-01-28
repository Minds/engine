<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Common\Repository\Response;
use Minds\Entities\User;

interface ManagerInterface
{
    public function getUnseenTopEntities(User $targetUser, int $totalEntitiesToRetrieve): Response;
}
