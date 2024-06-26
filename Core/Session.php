<?php

namespace Minds\Core;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Common\Cookie;
use Minds\Entities\User;
use Sentry;

/**
 * Minds Session Manager
 * @todo Session Name should be configurable
 */
class Session extends base
{
    private static $user;

    private $session_name = 'minds';

    /** @var Config $config */
    private $config;

    public function __construct($config = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        header('X-Powered-By: Minds', true);
    }

    /**
     * Regenerates the session.
     * @param  bool  $new_id Regenerate the session ID too?
     * @param  User  $user   Current user override
     * @return null
     */
    public static function regenerate($new_id = true, $user = null)
    {
        error_log('DEPRECATED: session->regenerate');
    }

    /**
     * Construct the user via the OAuth middleware
     * @param $server
     * @return void
     */
    public static function withRouterRequest(&$request, &$response)
    {
        try {
            $server = Di::_()->get('OAuth\Server\Resource');
            $request = $server->validateAuthenticatedRequest($request);
            $user_guid = $request->getAttribute('oauth_user_id');
            static::setUserByGuid($user_guid);
        } catch (\Exception $e) {
            // var_dump($e);
        }
    }

    /**
     * Construct the user manually by guid
     * @param $user
     * @return void
     */
    public static function setUserByGuid($user_guid)
    {
        $user = Di::_()->get(EntitiesBuilder::class)->single($user_guid, [ 'cacheTtl' => 259200 ]);
        static::setUser($user);
    }

    /**
     * Construct the user manually
     * @param User $user
     * @return void
     */
    public static function setUser(?User $user): void
    {
        static::$user = $user;

        // Update sentry with the current user
        // TODO: Move to a delegate
        if ($user) {
            Sentry\configureScope(function (Sentry\State\Scope $scope) use ($user): void {
                $scope->setUser([
                    'id' => (string) $user->getGuid(),
                ]);
            });
        }

        if (
            !$user
            || !static::$user->username
            || static::$user->isBanned()
            || !static::$user->isEnabled()
        ) {
            static::$user = null; //bad user
        }
    }


    /**
     * Check if there's an user logged in
     * @return bool
     */
    public static function isLoggedin(): bool
    {
        $user = self::getLoggedinUser();

        if ((isset($user)) && ($user instanceof \ElggUser || $user instanceof User) && $user->guid) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current user is an administrator
     * @return bool
     */
    public static function isAdmin(): bool
    {
        if (!self::isLoggedin()) {
            return false;
        }

        $user = self::getLoggedinUser();
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get the logged in user's entity
     * @return User
     */
    public static function getLoggedinUser(): ?User
    {
        return static::$user;
    }

    /**
     * Get the logged in user's entity GUID
     * @return string
     */
    public static function getLoggedInUserGuid()
    {
        if ($user = self::getLoggedinUser()) {
            return $user->guid;
        }

        return false;
    }
}
