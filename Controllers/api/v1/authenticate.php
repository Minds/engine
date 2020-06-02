<?php
/**
 * Minds Subscriptions
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Core\Security;
use Minds\Core\Session;
use Minds\Core\Features;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\I18n\I18n;
use Minds\Exceptions\TwoFactorRequired;
use Minds\Core\Queue;
use Minds\Core\Subscriptions;

class authenticate implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * NOT AVAILABLE
     */
    public function get($pages)
    {
        return Factory::response(['status'=>'error', 'message'=>'GET is not supported for this endpoint']);
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
        if (!Core\Security\XSRF::validateRequest()) {
            return false;
        }

        $user = new Entities\User(strtolower($_POST['username']));

        /** @var Core\Security\LoginAttempts $attempts */
        $attempts = Core\Di\Di::_()->get('Security\LoginAttempts');

        if (!$user->username) {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            return Factory::response(['status' => 'failed']);
        }

        $attempts->setUser($user);

        if ($attempts->checkFailures()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'LoginException::AttemptsExceeded'
            ]);
        }

        if (!$user->isEnabled() && !$user->isBanned()) {
            $user->enable();
        }

        try {
            if (!Core\Security\Password::check($user, $_POST['password'])) {
                $attempts->logFailure();
                header('HTTP/1.1 401 Unauthorized', true, 401);
                return Factory::response(['status' => 'failed']);
            }
        } catch (Core\Security\Exceptions\PasswordRequiresHashUpgradeException $e) {
            $user->password = Core\Security\Password::generate($user, $_POST['password']);
            $user->override_password = true;
            $user->save();
        }

        $attempts->resetFailuresCount(); // Reset any previous failed login attempts

        try {
            Di::_()->get('Security\Events')
                ->onLogin($user);
        } catch (TwoFactorRequired $e) {
            header('HTTP/1.1 ' + $e->getCode(), true, $e->getCode());
            $response['status'] = "error";
            $response['code'] = $e->getCode();
            $response['message'] = $e->getMessage();
            return Factory::response($response);
        }

        // set language and language cookie
        $i18n = new I18n();
        $language = $i18n->getLanguage();
        $user->setLanguage($language);
        $i18n->setLanguageCookie($language);

        $sessions = Di::_()->get('Sessions\Manager');
        $sessions->setUser($user);
        $sessions->createSession();
        $sessions->save(); //save to db and cookie

        \set_last_login($user); // TODO: Refactor this

        Session::generateJWTCookie($sessions->getSession());
        Security\XSRF::setCookie(true);

        // Set the canary cookie
        Di::_()->get('Features\Canary')
            ->setCookie($user->isCanary());

        $response['status'] = 'success';
        $response['user'] = $user->export();

        return Factory::response($response);
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
        /** @var Core\Sessions\Manager $sessions */
        $sessions = Di::_()->get('Sessions\Manager');

        if (isset($pages[0]) && $pages[0] === 'all') {
            $sessions->destroy(true);
        } else {
            $sessions->destroy(false);
        }

        return Factory::response([]);
    }
}
