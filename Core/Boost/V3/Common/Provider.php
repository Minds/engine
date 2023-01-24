<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Common;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        Di::_()->bind(ViewsScroller::class, function ($di): ViewsScroller {
            return new ViewsScroller();
        });
    }
}
