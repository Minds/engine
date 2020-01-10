<?php

namespace Minds\Core\Payments;

use Minds\Core\Di\Di;

class Factory
{
    public static function build($handler, $opts = [])
    {
        switch (ucfirst($handler)) {
          default:
            throw new \Exception("Service not found");
        }
    }
}
