<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Minds\Core\Feeds\Elastic\Entities;

interface ManagerInterface
{
    public function getUnseenTopEntities(): Entities;
}
