<?php
/**
 * Password security functions
 */
namespace Minds\Core\Security;

use Minds\Common\IpAddress;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Entities;
use Minds\Entities\User;

class Password
{
    /** @var KeyValueLimiter */
    protected $kvLimiter;

    public function __construct(KeyValueLimiter $kvLimiter = null)
    {
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
    }

    /**
     * Check if a password is valid
     * @param mixed $user
     * @param string $password
     * @throws Exceptions\PasswordRequiresHashUpgradeException
     * @return boolean
     */
    public function check($user, $password)
    {
        if (is_numeric($user) || is_string($user)) {
            $user = new Entities\User($user);
        }

        // Rate limits defined and executed here
        $this->checkRateLimits($user);

        // if the password was generated using password_hash, then return, otherwise try other algorithms
        if (password_verify($password, $user->password)) {
            return true;
        }

        $algo = 'sha256';
        $length = strlen($user->password);
        if ($length == 32) { //legacy users might still be using md5
            $algo = 'md5';
        }

        $matches = $user->password === self::generate($user, $password, $algo);

        if ($matches) {
            throw new Core\Security\Exceptions\PasswordRequiresHashUpgradeException();
        }

        return false;
    }

    /**
     * @param User $user
     * @throws RateLimitExceededException
     */
    protected function checkRateLimits(User $user)
    {
        if ($user) {
            // Limit by user guid
            $this->kvLimiter
                ->setKey('password-attempts-user_guid')
                ->setValue($user->getGuid())
                ->setSeconds(3600)
                ->setMax(35)
                ->checkAndIncrement();
        }
    
        // Limit by IP
        $this->kvLimiter
                ->setKey('password-attempts-ip')
                ->setValue((new IpAddress)->get())
                ->setSeconds(3600)
                ->setMax(35)
                ->checkAndIncrement();
    }

    /**
     * Generate a password
     * @param entities\User $user
     * @param string $password
     * @param string $algo (optional)
     * @return string
     */
    public static function generate($user, $password, $algo = "bcrypt")
    {
        if ($algo == 'md5') {
            return md5($password . $user->salt);
        } elseif ($algo == 'sha256') {
            return hash('sha256', $password . $user->salt);
        }

        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Return a salt Value
     * @return string
     */
    public static function salt()
    {
        return substr(hash('sha256', microtime() . rand()), 0, 8);
    }

    /**
     * Return a reset code for a forgotten password
     * @param entities\User $user
     * @return string
     */
    public static function reset($user)
    {
        $bytes = openssl_random_pseudo_bytes(128);
        $code = hash('sha512', $bytes);
        $user->password_reset_code = $code;
        $user->save();
        return $code;
    }

    /**
     * Reset password to a random string
     * @param entities\User $user
     * @return boolean
     */
    public function randomReset($user): bool
    {
        $randomPassword = hash('sha512', openssl_random_pseudo_bytes(128));

        $user->password = self::generate($user, $randomPassword);
        $user->override_password = true;
        $user->save();

        return true;
    }
}
