<?php
/**
 * ACL Security handlers
 */
namespace Minds\Core\Security;

use Minds\Core;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\RateLimits\Manager as RateLimitsManager;
use Minds\Entities;
use Minds\Entities\Entity;
use Minds\Entities\RepositoryEntity;
use Minds\Entities\User;
use Minds\Exceptions\StopEventException;
use Minds\Helpers\Flags;
use Minds\Helpers\MagicAttributes;

class ACL
{
    private static $_;
    public static $ignore = false;

    /** @var RateLimitsManager $rateLimits */
    private $rateLimits;

    public function __construct($rateLimits = null)
    {
        $this->rateLimits = $rateLimits ?: new RateLimitsManager;
    }

    /**
     * Initialise default ACL constraints
     */
    private function init()
    {
        ACL\Block::_()->listen();
    }

    /**
     * Override the ACL and return the previous status
     * @param boolean $ignore
     * @return boolean
     */
    public function setIgnore($ignore = false)
    {
        $previous = self::$ignore;
        self::$ignore = $ignore;
        return $previous;
    }

    /**
     * Checks access read rights to entity
     * @param Entity $entity
     * @param $user optional
     * @param $strict optional. skips public access checks
     * @return boolean
     */
    public function read($entity, $user = null)
    {
        if (!$user) {
            $user = Core\Session::getLoggedinUser();
        }

        if (self::$ignore == true) {
            return true;
        }

        if (Flags::shouldFail($entity)) {
            return false;
        }

        // If logged out and public and not container
        if (!Core\Session::isLoggedIn()) {
            if (
                (int) $entity->access_id == ACCESS_PUBLIC
                && (
                    $entity->owner_guid == $entity->container_guid
                    || $entity->container_guid == 0
                )
            ) {
                return true;
            } else {
                if (Core\Events\Dispatcher::trigger('acl:read', $entity->getType(), [
                    'entity' => $entity,
                    'user' => $user
                    ], false) === true
                ) {
                    return true;
                }
                return false;
            }
        }

        /**
         * Does the user ownn the entity, or is it the container?
         */
        if ($entity->owner_guid && ($entity->owner_guid == $user->guid)) {
            return true;
        }

        if ($entity->container_guid && ($entity->container_guid == $user->guid)) {
            return true;
        }

        /**
         * Is the entity open for loggedin users?
         * And check the owner is the container_guid too
         */
        if (
            in_array($entity->getAccessId(), [ACCESS_LOGGED_IN, ACCESS_PUBLIC], false)
            && (
                $entity->owner_guid == $entity->container_guid
                || $entity->container_guid == 0
            )
        ) {
            return true;
        }

        /**
         * If marked as unlisted and we don't have a container_guid matching owner_guid
         */
        if ($entity->getAccessId() == 0 && $entity->owner_guid == $entity->container_guid) {
            return true;
        }

        /**
         * Is this user an admin?
         */
        if ($user && $user->isAdmin()) {
            return true;
        }

        //$access_array = get_access_array($user->guid, 0);
        //if(in_array($entity->access_id, $access_array) || in_array($entity->container_guid, $access_array) || in_array($entity->guid, $access_array)){
        //  return true;
        //}

        /**
         * Allow plugins to extend the ACL check
         */
        if (Core\Events\Dispatcher::trigger('acl:read', $entity->getType(), ['entity'=>$entity, 'user'=>$user], false) === true) {
            return true;
        }

        return false;
    }

    /**
     * Checks access read rights to entity
     * @param Entity|RepositoryEntity $entity
     * @param User $user (optional)
     * @return boolean
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    public function write($entity, $user = null)
    {
        if (!$user) {
            $user = Core\Session::getLoggedinUser();
        }

        if (self::$ignore == true) {
            return true;
        }

        if (!$user) {
            return false;
        }

        /**
         * If the user is banned or in a limited state
         */
        if ($user->isBanned() || !$user->isEnabled()) {
            return false;
        }

        /**
         * If the user hasn't verified the email
         */
        if (!$user->isTrusted()) {
            throw new UnverifiedEmailException();
        }

        /**
         * Does the user own the entity, or is it the container?
         */
        if ($entity->owner_guid
            && ($entity->owner_guid == $user->guid)
            && (
                !$entity->container_guid // there is no container guid
                || ($entity->container_guid == $user->guid) // or it is the same as owner
            )
        ) {
            return true;
        }

        /**
         * Check if its the same entity (is user)
         */
        if ((isset($entity->guid) && $entity->guid == $user->guid) ||
            MagicAttributes::getterExists($entity, 'getGuid') && $entity->getGuid() == $user->guid) {
            return true;
        }

        /**
         * Is this user an admin?
         */
        if ($user->isAdmin()) {
            return true;
        }

        /**
         * Allow plugins to extend the ACL check
         */
        $type = property_exists($entity, 'type') ? $entity->type : 'all';
        if (Core\Events\Dispatcher::trigger('acl:write', $entity->type, ['entity'=>$entity, 'user'=>$user], false) === true) {
            return true;
        }

        /**
         * Allow plugins to check if we own the container
         */
        if ($entity->container_guid
            && $entity->container_guid != $entity->owner_guid
            && $entity->container_guid != $entity->guid
        ) {
            if (isset($entity->containerObj) && $entity->containerObj) {
                $container = Core\Entities::build($entity->containerObj);
            } else {
                $container = Entities\Factory::build($entity->container_guid);
            }

            $check = Core\Events\Dispatcher::trigger('acl:write:container', $container->type, [
                'entity' => $entity,
                'user' => $user,
                'container' => $container
            ], false);

            if ($check === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user can interact with the entity
     * @param Entity $entity
     * @param (optional) $user
     * @return boolean
     */
    public function interact($entity, $user = null, $interaction = null)
    {
        if (!$user || !is_object($user)) {
            $user = Core\Session::getLoggedinUser();
        }

        /**
         * Logged out users can not interact
         */
        if (!$user) {
            return false;
        }

        /**
         * If the user is banned or in a limited state
         */
        if ($user->isBanned() || !$user->isEnabled()) {
            return false;
        }

        /**
         * If the user hasn't verified the email
         */
        if (!$user->isTrusted()) {
            throw new UnverifiedEmailException();
        }

        /**
         * Check if we are the owner
         */
        if (
            ($entity->owner_guid && $entity->owner_guid == $user->guid) ||
            ($entity->container_guid && $entity->container_guid == $user->guid) ||
            ($entity->guid && $entity->guid == $user->guid)
        ) {
            return true;
        }

        /**
         * Is this user an admin?
         */
        if ($user->isAdmin()) {
            return true;
        }

        $rateLimited = $this->rateLimits
            ->setUser($user)
            ->setEntity($entity)
            ->setInteraction($interaction)
            ->isLimited();

        if ($rateLimited) {
            return false;
        }

        /**
         * Allow plugins to extend the ACL check
         */
        $event = Core\Events\Dispatcher::trigger('acl:interact', $entity->type, [
                    'entity'=>$entity,
                    'user'=>$user,
                    'interaction' => $interaction,
                ], null);

        if ($event === false) {
            return false;
        }

        return true;
    }

    public static function _()
    {
        if (!self::$_) {
            self::$_ = new ACL();
            self::$_->init();
        }
        return self::$_;
    }
}
