<?php
/**
 * Minds Two Factor
 *
 * @version 1
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Security;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Core\SMS\Exceptions\VoIpPhoneException;
use Minds\Helpers\FormatPhoneNumber;
use Minds\Entities;
use Minds\Interfaces;
use Zend\Diactoros\ServerRequestFactory;

class twofactor implements Interfaces\Api
{
    /**
     * NOT AVAILABLE
     */
    public function get($pages)
    {
        $response = [];

        $user = Core\Session::getLoggedInUser();
        $response['telno'] = $user->telno;

        return Factory::response($response);
    }

    /**
     * Registers a user
     * @param array $pages
     *
     * @SWG\Post(
     *     summary="Create a new channel",
     *     path="/v1/register",
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function post($pages)
    {
        $twofactor = new Security\TwoFactor();
        $user = Core\Session::getLoggedInUser();
        $response = [];

        if (!isset($pages[0])) {
            $pages[0] = '';
        }

        switch ($pages[0]) {
            case "remove":
                $validator = Di::_()->get('Security\Password');

                if (!$validator->check(Core\Session::getLoggedinUser(), $_POST['password'])) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Password incorrect'
                    ]);
                }

                $user = Core\Session::getLoggedInUser();
                $user->twofactor = false;
                $user->telno = false;

                $save = new Save();

                $save
                    ->setEntity($user)
                    ->withMutatedAttributes([
                        'twofactor',
                        'telno',
                    ])
                    ->save();

                break;
        }

        return Factory::response($response);
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
    }
}
