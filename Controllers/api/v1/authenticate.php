<?php
/**
 * Minds Subscriptions
 *
 * @version 1
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Minds\Api\Factory;
use Minds\Common\IpAddress;
use Minds\Common\PseudonymousIdentifier;
use Minds\Core;
use Minds\Core\Analytics;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security;
use Minds\Core\Security\ACL;
use Minds\Core\Security\RateLimits\RateLimitExceededException;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Session;
use Minds\Entities;
use Minds\Interfaces;
use Zend\Diactoros\ServerRequestFactory;

/**
 * @deprecated
 */
class authenticate implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    private EntitiesBuilder $entitiesBuilder;
    private Save $save;
    private ACL $acl;

    public function __construct()
    {
        $this->entitiesBuilder = Di::_()->get(EntitiesBuilder::class);
        $this->save = new Save();
        $this->acl = Di::_()->get(ACL::class);
    }

    /**
     * NOT AVAILABLE
     */
    public function get($pages)
    {
        return Factory::response(['status' => 'error', 'message' => 'GET is not supported for this endpoint']);
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
        $request = ServerRequestFactory::fromGlobals();
        if (!Core\Security\XSRF::validateRequest($request)) {
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

        $user = $this->entitiesBuilder->getByUserByIndex(strtolower($_POST['username']));

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
            $this->save
                ->setEntity($user)
                ->withMutatedAttributes(['password'])
                ->save();
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

        if (!$user->isEnabled() && !$user->isBanned()) {
            $ignore = $this->acl::$ignore;
            $this->acl::$ignore = true;

            $user->enabled = 'yes';
            $this->save
                ->setEntity($user)
                ->withMutatedAttributes(['enabled'])
                ->save();

            $this->acl::$ignore = $ignore;
        }

        $sessions = Di::_()->get('Sessions\Manager');
        $sessions->setUser($user);
        $sessions->createSession();
        $sessions->save(); // save to db and cookie

        \set_last_login($user); // TODO: Refactor this

        Security\XSRF::setCookie(true);

        // Set the canary cookie
        Di::_()->get('Features\Canary')
            ->setCookie($user->isCanary());

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

        // Return permissions
        $response['permissions'] = array_map(function ($permission) {
            return $permission->name;
        }, Di::_()->get(RolesService::class)->getUserPermissions($user));

        // Return analytics opt out
        $response['opt_out_analytics'] = $user->isOptOutAnalytics();

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
