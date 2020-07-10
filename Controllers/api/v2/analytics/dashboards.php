<?php


namespace Minds\Controllers\api\v2\analytics;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Session;
use Minds\Core\Di\Di;
use Minds\Common\Cookie;
use Minds\Entities;
use Minds\Helpers\Counters;
use Minds\Interfaces;

class dashboards implements Interfaces\Api
{
    public function get($pages)
    {
        $user = Session::getLoggedInUser();
        if (!$user) {
            return ([
                'error' => 'You must be logged in to view analytics dashboards'
            ]);
        }

        $dashboardsManager = Di::_()->get('Analytics\Dashboards\Manager');

        $id = $pages[0] ?? 'unknown';

        $dashboard = $dashboardsManager->getDashboardById($id);

        $dashboard->setUser($user);

        if (isset($_GET['timespan'])) {
            $dashboard->setTimespanId($_GET['timespan']);
        }

        if (isset($_GET['filter'])) {
            $filterIds = explode(',', $_GET['filter']);
            $dashboard->setFilterIds($filterIds);
        }

        if (isset($_GET['metric'])) {
            $dashboard->setMetricId($_GET['metric']);
        }

        return Factory::response([
            'dashboard' => $dashboard->export(),
        ]);
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
