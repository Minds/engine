<?php

namespace Minds\Controllers\api\v2\boost\campaigns;

use Minds\Api\AbstractApi;
use Minds\Core\Analytics\EntityCentric\BoostViewsDaily;

class analytics extends AbstractApi
{
    public function get($pages): void
    {
        switch ($pages[0]) {
            case 'rate':
                // Get current boost rate
                $avgRate = (new BoostViewsDaily())->lastSevenDays()->getAvg();
                $this->send(['rate' => $avgRate]);
                break;
            case 'days':
                $days = (new BoostViewsDaily())->lastSevenDays()->getAll();
                $this->send(['days' => $days]);
                break;
            default:
                $this->sendBadRequest();
        }
    }
}
