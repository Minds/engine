<?php
namespace Minds\Core;

use Minds\Core\Di\Di;
use Minds\Core\Log\Log;
use Minds\Entities as Entity;

class Sandbox
{
    public static function user($default, $sandbox = 'default')
    {
        $config = Di::_()->get('Config')->get('sandbox');

        if (!$config) {
            return $default;
        }

        if (!$config['enabled']) {
            return $default;
        }

        $guid = $config[$sandbox]['guid'];
        Log::debug(json_encode($config), static::class);

        Log::info('Sandboxing user ' . $guid, static::class);
        return new Entity\User($guid);
    }
}
