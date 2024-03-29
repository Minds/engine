<?php

namespace Minds\Common;

use Minds\Core\Di\Di;
use Minds\Traits\MagicAttributes;

/**
 * Class Cookie
 * @method Cookie setName(string $name)
 * @method Cookie setValue(string $value)
 * @method Cookie setExpire(int $value)
 * @method Cookie setPath(string $path)
 * @method Cookie setDomain(string $domain)
 * @method Cookie setSecure(bool $secure)
 * @method Cookie setHttpOnly(bool $httpOnly)
 * @method Cookie setSameSite(string $sameSite)
 */
class Cookie
{
    use MagicAttributes;

    /** @var CONFIG $config */
    private $config;

    /** @var string $name */
    private $name;

    /** @var string $value */
    private $value = '';

    /** @var int $expire */
    private $expire = 0;

    /** @var string $path */
    private $path = '';

    /** @var string $domain */
    private $domain = '';

    /** @var bool $secure */
    private $secure = true;

    /** @var bool $httOonly */
    private $httpOnly = true;

    /** @var string */
    private $sameSite = null;

    public function __construct($config = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * Create the cookie
     * @return void
     */
    public function create()
    {
        if ($this->config->disable_secure_cookies) {
            $this->secure = false;
        }

        if (headers_sent()) {
            return false;
        }

        if (isset($_COOKIE['disable_cookies']) && $this->name != 'disable_cookies') {
            $this->expire = time() - 3600;
            $this->value = '';
        }

        setcookie($this->name, $this->value, [
            'expires' => $this->expire,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => !$this->secure && $this->sameSite === 'None' ? null : $this->sameSite, // You can't have non-secure and sameSite=None
        ]);
        $_COOKIE[$this->name] = $this->value; //set the global cookie
    }
}
