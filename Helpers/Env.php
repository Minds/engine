<?php
namespace Minds\Helpers;

/**
 * Helper for parsing environment variables
 */
class Env
{
    public const ENV_PREFIX = "MINDS_ENV_";
    public const ENV_ARRAY_DELIMITER = "__";

    /**
     * Load the environment variables defined on the system or in the root .env
     * Set key value pairs for simple envs
     * Create nested arrays for keys with delimiters
     */
    public static function getMindsEnv() : array
    {
        $config = [];
        foreach ($_ENV as $envKey => $value) {
            if (empty($value) || !static::isMindsEnv($envKey)) {
                continue;
            }
            $keyPieces = explode(static::ENV_ARRAY_DELIMITER, substr($envKey, strlen(static::ENV_PREFIX)));
            $config = array_merge_recursive($config, static::nestArray($keyPieces, $value));
        }
        return $config;
    }

    /**
     * Is the provided key a Minds environment variable
     * @param string $key
     * @return bool
     */
    public static function isMindsEnv($key) : bool
    {
        return (substr($key, 0, strlen(static::ENV_PREFIX)) === static::ENV_PREFIX);
    }

    /**
     * Takes and array and turns it into a multidimensional array
     * Sets the last level to the passed in value
     * Recursive
     * @param array $keys A one dimensional array of strings to turn into a nested value
     * @param mixed $value The value to set on the last level of the array
     */
    public static function nestArray(array $keys, $value)
    {
        //Recursion check, if we have no more keys, set the value
        if (count($keys) === 0) {
            return static::cast($value);
        }
        //Anything that isn't the last is the next level of the tree
        $firstValue = array_shift($keys);
        return [$firstValue => static::nestArray($keys, $value)];
    }

    /**
     * All values coming in from the env are treated as strings
     * This lazily attempts to set their appropriate type
     * Use the string values of true and false in the config files
     * As integers will be cast as integers
     * @param mixed A posix environment variable value
     * @return mixed the cast value
     */
    public static function cast($value)
    {
        if (is_array($value)) {
            return $value;
        }
        try {
            if (strtolower($value) === 'true') {
                return true;
            }
            if (strtolower($value) === 'false') {
                return false;
            }
            if (is_numeric($value)) {
                //See if it's a float and not an integer
                if (is_float($value) || ((float) $value != (int) $value)) {
                    return floatval($value);
                };
                return intval($value);
            }
        } catch (\Error $ex) {
            Log::warning("Could not cast value: {$value} - " . $ex->getMessage());
        } catch (\Exception $ex) {
            Log::warning("Could not cast value: {$value} - " . $ex->getMessage());
        }
        return $value;
    }
}
