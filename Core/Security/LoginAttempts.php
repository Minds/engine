<?php


namespace Minds\Core\Security;

use Minds\Core\Config;
use Minds\Core\Data\Redis\Client as RedisServer;
use Minds\Core\Di\Di;
use Minds\Core\Security\Exceptions\UserNotSetupException;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Entities\User;
use RedisException;

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
     * @throws RedisException
     */
    public function logFailure(): bool
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
    public function checkFailures(): bool
    {
        if ($this->kvLimiter->verifyBypass()) {
            return false;
        }
        
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
    public function resetFailuresCount(): bool
    {
        if (!$this->user) {
            throw new UserNotSetupException();
        }
    
        $user_guid = (int) $this->user->guid;

        if ($user_guid) {
            $this->getRedis()->delete("login-failures:$user_guid");
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
            // TODO fully move to Redis HA
            $redisHa = ($this->config->redis['ha']) ?? null;
            if ($redisHa) {
                $master = ($this->config->redis['master']['host']) ?? null;
                $masterPort = ($this->config->redis['master']['port']) ?? null;
                
                $this->redis->connect($master, $masterPort);
            } else {
                $this->redis->connect($this->config->redis['master']);
            }
            $this->redisIsConnected = true;
        }
        return $this->redis;
    }
}
