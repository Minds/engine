<?php
/**
 * Sendgrid Webhooks
 *
 * @author mark
 */

namespace Minds\Controllers\api\v2\email;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\Webhooks;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Interfaces;

class sendgrid implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * GET method
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * POST method
     */
    public function post($pages)
    {
        /** @var Webhooks */
        $webhooks = Di::_()->get('SendGrid\Webhooks');
        $webhooks->setAuthKey($pages[0]);

        $data = file_get_contents("php://input");
        $events = json_decode($data, true);

        $webhooks->process($events);

        return Factory::response([]);
    }

    /**
     * PUT method
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * DELETE method
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
