<?php
namespace Minds\Core\Config;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;
use Minds\Helpers\Env;

/**
 * Minds Config Providers
 */
class ConfigProvider extends Provider
{
    /**
     * Registers providers onto DI
     * @return null
     */
    public function register()
    {
        $this->di->bind(Config::class, function (Di $di): Config {
            global $CONFIG;

            if (!isset($CONFIG)) {
                $CONFIG = new Config();
            }

            // Load the system settings
            if (file_exists(__MINDS_ROOT__ . '/settings.php')) {
                include_once __MINDS_ROOT__ . '/settings.php';
            }

            // Load environment values
            $env = Env::getMindsEnv();
            foreach ($env as $key => $value) {
                $CONFIG->set($key, $value, ['recursive' => true]);
            }

            return $CONFIG;
        }, ['useFactory'=>true]);

        $this->di->bind('Config', function ($di) {
            return $di->get(Config::class);
        }, ['useFactory'=>true]);

        $this->di->bind('Config\Exported', function ($di) {
            return new Exported();
        }, ['useFactory' => true]);
    }
}
