<?php


namespace Minds\Core\Security;

use Minds\Core\Config;
use Minds\Core\Data\Redis\Client as RedisServer;
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

    /** @var RedisServer */
    private $redis;

    /** @var Config */
    private $config;

    /** @var bool */
    protected $redisIsConnected = false;

    public function __construct($kvLimiter = null, $redis = null, $config = null)
    {
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
        $this->redis = $redis ?: new RedisServer();
        $this->config = $config ?? Di::_()->get('Config');
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
            $recordKey = "login-failures:$user_guid";

            $this->getRedis()->multi()
                ->incr($recordKey)
                ->expire($recordKey, 600) // 10 mins
                ->exec();

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
            $recordKey = "login-failures:$user_guid";
            $fails = (int) $this->getRedis()->get("$recordKey");
            if ($fails > 10) {
                return true;
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
                $this->getRedis()->delete("login-failures:$user_guid");

                return true;
            }

            // nothing to reset
            return true;
        }

        return false;
    }

    /**
     * Get our redis connection
     * @return RedisServer
     */
    private function getRedis(): RedisServer
    {
        if (!$this->redisIsConnected && $this->config->redis) {
            $this->redis->connect($this->config->redis['master']);
            $this->redisIsConnected = true;
        }
        return $this->redis;
    }
}
