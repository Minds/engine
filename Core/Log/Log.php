<?php
/**
 * Log facade
 * @author edgebal
 */

namespace Minds\Core\Log;

use Minds\Core\Config;
use Minds\Core\Di\Di;

class Log
{
    /** @var Config */
    protected $config;

    /** @var LoggerContext[] */
    protected $contexts = [];

    /** @var Log */
    protected static $instance;

    /**
     * Logger constructor.
     * @param Config $config
     */
    public function __construct(
        $config = null
    )
    {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param $context
     * @return LoggerContext
     */
    public function get($context)
    {
        if (!isset($this->contexts[$context])) {
            $this->contexts[$context] = (new LoggerContext())
                ->setContext($context);
        }

        return $this->contexts[$context];
    }

    /**
     * @return Log
     */
    public static function _()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function emergency($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->emergency($message, $payload);
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function alert($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->alert($message, $payload);
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function critical($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->critical($message, $payload);
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function error($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->error($message, $payload);
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function warning($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->warning($message, $payload);
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function notice($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->notice($message, $payload);
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function info($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->info($message, $payload);
    }

    /**
     * @param string $message
     * @param string $context
     * @param array $payload
     * @return bool
     */
    public static function debug($message, $context = 'default', array $payload = [])
    {
        return static::_()->get($context)->debug($message, $payload);
    }
}
