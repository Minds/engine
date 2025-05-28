<?php
/**
 * Minds Subscriptions
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ForgotPassword\Services\ForgotPasswordService;
use Minds\Core\Security\RateLimits\RateLimitExceededException;
use Zend\Diactoros\ServerRequestFactory;

class forgotpassword implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /** @var ActionButton */
    protected $actionButton;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ForgotPasswordService $forgotPasswordService = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
        $this->forgotPasswordService ??= Di::_()->get(ForgotPasswordService::class);
    }

    /**
     * NOT AVAILABLE
     */
    public function get($pages)
    {
        return Factory::response(['status'=>'error', 'message'=>'GET is not supported for this endpoint']);
    }

    /**
     * Resets a forgotten password
     * @param array $pages
     *
     * @SWG\Post(
     *     summary="Reset a password",
     *     path="/v1/forgotpassword",
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function post($pages)
    {
        $response = [];

        if (!isset($pages[0])) {
            $pages[0] = "request";
        }

        switch ($pages[0]) {
            case "request":

                try {
                    Di::_()->get("Security\RateLimits\KeyValueLimiter")
                        ->setKey('forgot-password-ips')
                        ->setValue($_SERVER['HTTP_X_FORWARDED_FOR'])
                        ->setSeconds(3600) // Per hour
                        ->setMax(5)
                        ->checkAndIncrement();

                    Di::_()->get("Security\RateLimits\KeyValueLimiter")
                        ->setKey('forgot-password-ips')
                        ->setValue($_SERVER['HTTP_X_FORWARDED_FOR'])
                        ->setSeconds(86400) // Per day
                        ->setMax(20)
                        ->checkAndIncrement();
                } catch (RateLimitExceededException $e) {
                    $response['status'] = "error";
                    $response['message'] = $e->getMessage();
                    break;
                }

                $user = $this->entitiesBuilder->getByUserByIndex(strtolower($_POST['username']));
                if (!$user) {
                    break;
                }

                $this->forgotPasswordService->request($user);
                break;
            case "reset":
                $user = $this->entitiesBuilder->getByUserByIndex(strtolower($_POST['username']));
                if (!$user->guid) {
                    $response['status'] = "error";
                    $response['message'] = "Could not find @" . $_POST['username'];
                    break;
                }

                if (!$user->password_reset_code) {
                    $response['status'] = "error";
                    $response['message'] = "Please try again with a new reset code.";
                    break;
                }

                if ($user->password_reset_code && $user->password_reset_code !== $_POST['code']) {
                    $response['status'] = "error";
                    $response['message'] = "The reset code is invalid";
                    break;
                }

                if (!isset($_POST['code']) || !$_POST['code'] || !is_string($_POST['code']) || !strlen($_POST['code']) > 10) {
                    $response['status'] = "error";
                    $response['message'] = "The reset code is invalid";
                    break;
                }

                try {
                    if (!validate_password($_POST['password'])) {
                        $response['status'] = "error";
                        $response['message'] = "Password must have more than 8 characters. Including uppercase, numbers, special characters (ie. !,#,@), and cannot have spaces.";
                    }
                } catch (\Exception $e) {
                    $response['status'] = "error";
                    $response['message'] = "Password must have more than 8 characters. Including uppercase, numbers, special characters (ie. !,#,@), and cannot have spaces.";
                    break;
                }

                Di::_()->get('Security\TwoFactor\Manager')->gatekeeper(
                    $user,
                    ServerRequestFactory::fromGlobals(),
                    enableEmail: false
                );

                $this->forgotPasswordService->reset(
                    $user,
                    $_POST['code'],
                    $_POST['password']
                );

                $response['user'] = $user->export();
                break;
            default:
                $response = ['status'=>'error', 'message'=>'Unknown endpoint'];
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
