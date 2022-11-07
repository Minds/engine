<?php

namespace Minds\Core\Settings;

use Minds\Core\Di\Di;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Settings\Manager');
    }
}
