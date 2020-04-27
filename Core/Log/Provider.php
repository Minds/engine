<?php
/**
 * Provider
 *
 * @author edgebal
 */

namespace Minds\Core\Log;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

/**
 * DI Provider bindings
 *
 * @package Minds\Core\Log
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Logger', function ($di) {
            /** @var Di $di */

            /** @var Config|false $config */
            $config = $di->get('Config');

            $options = [
                'isProduction' => $config ? !$config->get('development_mode') : true,
                'devToolsLogger' => $config ? $config->get('devtools_logger') : '',
                'minLogLevel' => $config ? $config->get('min_log_level') : null,
            ];

            return new Logger('Minds', $options);
        }, [ 'useFactory' => false ]);

        $this->di->bind('Logger\Singleton', function ($di) {
            /** @var Di $di */
            return $di->get('Logger');
        }, [ 'useFactory' => true ]);
    }
}
