<?php
/**
 * Provider
 * @author edgebal
 */

namespace Minds\Core\Front;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Front\Index', function () {
            return new Index();
        }, [ 'useFactory' => true ]);
    }
}
