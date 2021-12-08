<?php
namespace Minds\Core\Experiments\Cookie;

use Minds\Common\Cookie;

/**
 * Manager handling a cookie that contains an anonymous user's generated userId for Growthbook -
 * allows us to track analytics for logged out states.
 *
 * Cookie should be deleted on login, and registration before analytics events fire
 * to avoid misreported experiment ids, except when running experiments that track
 * session creation metrics for users that were previously anonymous (signup, login) -
 * in which case the cookie should be deleted AFTER the metrics event has fired.
 *
 * For example when testing whether a change on the homepage has an impact on signups
 * We need this cookie to exist with the generated anonymous ID until we have fired the metrics
 * event for signup so that Growthbook can connect the dots and see that the user that was
 * experimented on signed up - after that, it should be deleted or it would override the ID
 * for other metrics like active, pageviews etc.
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
            ->setExpire(0) // expires at end of session.
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
