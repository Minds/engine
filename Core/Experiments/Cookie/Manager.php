<?php
namespace Minds\Core\Experiments\Cookie;

use Minds\Common\Cookie;

/**
 * Manager handling a cookie that contains an bespoke ID for a user for consumption
 * by Growthbook - allows us to track analytics between logged in and out states.
 */
class Manager
{
    // fixed value for cookie name.
    private const COOKIE_NAME = 'experiments_id';

    public function __construct(
        private ?Cookie $cookie = null,
    ) {
        $this->cookie = $this->cookie ?? new Cookie();
    }

    /**
     * Sets cookie value.
     * @param string $id - experiments id to set for user.
     * @return self
     */
    public function set(string $id): self
    {
        $this->cookie
            ->setName(self::COOKIE_NAME)
            ->setValue($id)
            ->setExpire(strtotime("+1 year"))
            ->setPath('/')
            ->setHttpOnly(false) // browser needs to be able to read this cookie
            ->create();
        return $this;
    }

    /**
     * Cookie direct from superglobals.
     * We cannot use ServerRequest here because if you 'delete' and 'get' a cookie
     * in the same request, the ServerRequest object will hold stale values.
     * @return string cookie value.
     */
    public function get(): string
    {
        return $_COOKIE[self::COOKIE_NAME] ?? '';
    }

    /**
     * Set a null value for Cookie.
     * @return self
     */
    public function delete(): self
    {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $this->set('');
        }
        return $this;
    }
}
