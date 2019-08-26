<?php
/**
 * Minds Admin: Analytics : Signups
 *
 * @version 1
 * @author Mark Harding
 *
 */
namespace Minds\Controllers\api\v1\admin\analytics;

use Minds\Core;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use DateTime;

class signups implements Interfaces\Api, Interfaces\ApiAdminPam
{
    /**
     * Return analytics data
     * @param array $pages
     */
    public function get($pages)
    {
        $response = [];

        $db = new Core\Data\Call('entities_by_time');

        $app = Core\Analytics\App::_()
        ->setMetric('signup');

        $response['daily'] = $app->get(7);
        array_pop($response['daily']); //remove todays count
        $response['montly'] = $app->get(3, 'month');


        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
