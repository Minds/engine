<?php

/**
 * Features Manager
 *
 * @author emi
 */

namespace Minds\Core\Features;

use Minds\Core\Di\Di;
use Minds\Common\Cookie;
use Minds\Core\Session;

class Manager
{
    /** @var User $user */
    private $user;

    /** @var Config $config */
    private $config;

    /** @var Cookie $cookie */
    private $cookie;
    
    public function __construct($config = null, $cookie = null, $user = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->cookie = $cookie ?: new Cookie;
        $this->user = $user ?? Session::getLoggedInUser();
    }

    /**
     * Set the user
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Checks if a featured is enabled
     * @param $feature
     * @return bool
     */
    public function has($feature)
    {
        $features = $this->config->get('features') ?: [];

        if (!isset($features[$feature])) {
            // error_log("[Features\Manager] Feature '{$feature}' is not declared. Assuming false.");

            return false;
        }

        if ($features[$feature] === 'admin' && $this->user->isAdmin()) {
            return true;
        }

        if ($features[$feature] === 'canary' && $this->user && $this->user->get('canary')) {
            return true;
        }

        return $features[$feature] === true;
    }

    /**
     * Exports the features array
     * @return array
     */
    public function export()
    {
        return $this->config->get('features') ?: [];
    }

    /**
     * Set the canary cookie
     * @param bool $enabled
     * @return void
     */
    public function setCanaryCookie(bool $enabled = true) : void
    {
        $this->cookie
            ->setName('canary')
            ->setValue((int) $enabled)
            ->setExpire(0)
            ->setSecure(true) //only via ssl
            ->setHttpOnly(true) //never by browser
            ->setPath('/')
            ->create();
    }
}
