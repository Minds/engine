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
use Minds\Common\IpAddress;
use Minds\Common\PseudonymousIdentifier;
use Minds\Exceptions\TwoFactorRequired;
use Minds\Core\Queue;
use Minds\Core\Subscriptions;
use Minds\Core\Analytics;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\RateLimits\RateLimitExceededException;
use Zend\Diactoros\ServerRequestFactory;

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

        // Quick rate limit to make sure people aren't bombing this.
        // Note: the password rate limits are in Core\Security\Password->check

        Di::_()->get("Security\RateLimits\KeyValueLimiter")
                ->setKey('router-post-api-v1-authenticate')
                ->setValue((new IpAddress)->get())
                ->setSeconds(3600)
                ->setMax(100) // 100 times an hour
                ->checkAndIncrement();

        //

        $user = new Entities\User(strtolower($_POST['username']));

        /** @var Core\Security\LoginAttempts $attempts */
        $attempts = Core\Di\Di::_()->get('Security\LoginAttempts');

        if (!$user->username) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return Factory::response(['status' => 'failed']);
        }

        $attempts->setUser($user);

        try {
            if ($attempts->checkFailures()) {
                header('HTTP/1.1 429 Too Many Requests', true, 429);
                return Factory::response([
                    'status' => 'error',
                    'message' => 'LoginException::AttemptsExceeded'
                ]);
            }
        } catch (RateLimitExceededException $e) {
            header('HTTP/1.1 429 Too Many Requests', true, 429);
            return Factory::response([
                'status' => 'error',
                'message' => 'LoginException::AttemptsExceeded'
            ]);
        }

        if (!$user->isEnabled() && !$user->isBanned()) {
            $user->enable();
        }

        $password = $_POST['password'];

        try {
            $passwordSvc = new Core\Security\Password();
            if (!$passwordSvc->check($user, $password)) {
                $attempts->logFailure();
                header('HTTP/1.1 401 Unauthorized', true, 401);
                return Factory::response(['status' => 'failed']);
            }
        } catch (Core\Security\Exceptions\PasswordRequiresHashUpgradeException $e) {
            $user->password = Core\Security\Password::generate($user, $password);
            $user->override_password = true;
            $user->save();
        }

        $attempts->resetFailuresCount(); // Reset any previous failed login attempts

        try {
            $twoFactorManager = Di::_()->get('Security\TwoFactor\Manager');
            $twoFactorManager->gatekeeper($user, ServerRequestFactory::fromGlobals(), enableEmail: false);
        } catch (\Exception $e) {
            header('HTTP/1.1 ' . $e->getCode(), true, $e->getCode());
            $response['status'] = "error";
            $response['code'] = $e->getCode();
            $response['message'] = $e->getMessage();
            $response['errorId'] = str_replace('\\', '::', get_class($e));
            return Factory::response($response);
        }

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

        // delete experiments cookie as it will contain a logged-out placeholder guid.
        Di::_()->get('Experiments\Cookie\Manager')->delete();

        // Instantiate our pseudonymous identifier for analytics
        (new PseudonymousIdentifier())
            ->setUser($user)
            ->generateWithPassword($password);

        // Record login events
        $event = new Analytics\Metrics\Event();
        $event->setUserGuid($user->getGuid())
            ->setType('action')
            ->setAction('login')
            ->push();

        $response['status'] = 'success';
        $response['user'] = $user->export();

        return Factory::response($response);
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
        if (!Session::isLoggedin()) {
            throw new UnauthorizedException();
        }

        /** @var Core\Sessions\Manager $sessions */
        $sessions = Di::_()->get('Sessions\Manager');

        if (isset($pages[0]) && $pages[0] === 'all') {
            $sessions->deleteAll();
        } else {
            $sessions->delete();
        }

        return Factory::response([]);
    }
}
