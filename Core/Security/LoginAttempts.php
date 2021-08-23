<?php


namespace Minds\Core\Security;

use Minds\Common\IpAddress;
use Minds\Core\Di\Di;
use Minds\Core\Security\Exceptions\UserNotSetupException;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Entities\User;

class LoginAttempts
{
    /** @var User */
    protected $user;

    /** @var KeyValueLimiter */
    protected $kvLimiter;

    public function __construct($kvLimiter = null)
    {
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
    }

    /**
     * Sets the user
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Logs a failed login attempt
     * @return bool
     * @throws UserNotSetupException
     */
    public function logFailure()
    {
        if (!$this->user) {
            throw new UserNotSetupException();
        }
        $user_guid = (int) $this->user->guid;

        if ($user_guid) {
            $fails = (int) $this->user->getPrivateSetting("login_failures");
            $fails++;

            $this->user->setPrivateSetting("login_failures", $fails);
            $this->user->setPrivateSetting("login_failure_$fails", time());
            return true;
        }

        return false;
    }

    /**
     * Check if the user has exceeded the limit of attempts
     * @return bool
     * @throws UserNotSetupException
     */
    public function checkFailures()
    {
        if (!$this->user) {
            throw new UserNotSetupException();
        }
        // 5 failures in 1 minute causes temporary block on logins
        $limit = 5;
        $user_guid = (int) $this->user->guid;

        if ($user_guid) {

            // Bad place for this, but increment each time we check

            $period = 86400;

            $this->kvLimiter
                ->setKey('login-attempts-ip')
                ->setValue((new IpAddress)->get())
                ->setSeconds($period)
                ->setMax(1000) // 1000 ip logins per day
                ->checkAndIncrement();

            $fails = (int) $this->user->getPrivateSetting("login_failures");
            if ($fails >= $limit) {
                $cnt = 0;
                $time = time();
                for ($n = $fails; $n > 0; $n--) {
                    $f = $this->user->getPrivateSetting("login_failure_$n");
                    if ($f > $time - (60)) {
                        $cnt++;
                    } else {
                        // Cleanup as we go as this has expired
                        $this->user->removePrivateSetting("login_failure_$n");
                    }

                    if ($cnt == $limit) {
                        // Limit reached
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Resets failures attempts
     * @return bool
     * @throws UserNotSetupException
     */
    public function resetFailuresCount()
    {
        if (!$this->user) {
            throw new UserNotSetupException();
        }
        $user_guid = (int) $this->user->guid;

        if ($user_guid) {
            $fails = (int) $this->user->getPrivateSetting("login_failures");

            if ($fails) {
                for ($n = 1; $n <= $fails; $n++) {
                    $this->user->removePrivateSetting("login_failure_" . $n);
                }

                $this->user->removePrivateSetting("login_failures");

                return true;
            }

            // nothing to reset
            return true;
        }

        return false;
    }
}
