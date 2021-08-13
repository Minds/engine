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
use Minds\Core\Security;
use Minds\Core\SMS\Exceptions\VoIpPhoneException;
use Minds\Helpers\FormatPhoneNumber;
use Minds\Entities;
use Minds\Interfaces;

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

        $featuresManager = Di::_()->get('Features\Manager');
        $twilioVerify = Di::_()->get('SMS\Twilio\Verify');

        switch ($pages[0]) {
            case "setup":
                if ($featuresManager->has('twilio-verify')) {
                    if (!$twilioVerify->verify($this->number)) {
                        throw new VoIpPhoneException();
                    }

                    $number = FormatPhoneNumber::format($_POST['tel']);
                    $twilioVerify->send($number, '');

                    break;
                }

                $secret = $twofactor->createSecret();

                /** @var Core\SMS\SMSServiceInterface $sms */
                $sms = Core\Di\Di::_()->get('SMS');

                try {
                    if (!$sms->verify($_POST['tel'])) {
                        throw new VoIpPhoneException();
                    }
                } catch (\Exception $e) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ]);
                }

                $message = 'Minds TwoFactor code: '. $twofactor->getCode($secret);
                $number = FormatPhoneNumber::format($_POST['tel']);

                if ($sms->send($number, $message)) {
                    $response['secret'] = $secret;
                } else {
                    $response['status'] = "error";
                    $response['message'] = "Invalid number";
                }

                break;
            case "check":
                $secret = $_POST['secret'];
                $code = $_POST['code'];
                $telno = FormatPhoneNumber::format($_POST['telno']);

                if ($featuresManager->has('twilio-verify')) {
                    if ($twilioVerify->verifyCode($code, $telno)) {
                        $user->twofactor = true;
                        $user->telno = $telno;
                    } else {
                        $response['status'] = "error";
                        $response['message'] = "2factor code failed";
                        $user->twofactor = false;
                    }
                    $user->save();
                    break;
                }

                if ($twofactor->verifyCode($secret, $code, 1)) {
                    $response['status'] = "success";
                    $response['message'] = "2factor now setup";
                    $user->twofactor = true;
                    $user->telno = $telno;
                } else {
                    $response['status'] = "error";
                    $response['message'] = "2factor code failed";
                    $user->twofactor = false;
                }
                $user->save();
                break;
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
                $user->save();
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
