<?php
/**
 * confirmation
 *
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\email;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Interfaces;

class confirmation implements Interfaces\Api
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
        Factory::isLoggedIn();

        /** @var User $user */
        $user = Session::getLoggedinUser();

        switch ($pages[0] ?? '') {
            case 'resend':
                try {
                    /** @var Manager $emailConfirmation */
                    $emailConfirmation = Di::_()->get('Email\Confirmation');
                    $emailConfirmation
                        ->setUser($user)
                        ->sendEmail();

                    return Factory::response([
                        'sent' => true
                    ]);
                } catch (\Exception $e) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }

                break;
        }

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
