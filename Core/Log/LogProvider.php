<?php
/**
 * LogProvider
 * @author edgebal
 */

namespace Minds\Core\Log;

use Minds\Core\Di\Provider;

class LogProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Log', function() {
            return Log::_();
        }, [ 'useFactory' => true ]);
    }
}
