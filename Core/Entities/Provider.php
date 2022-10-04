<?php

declare(strict_types=1);

namespace Minds\Core\Entities;

use Minds\Core\Di\ImmutableException;

class Provider extends \Minds\Core\Di\Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            'Entities\Controller',
            function ($di) {
                return new Controller();
            }
        );
    }
}
