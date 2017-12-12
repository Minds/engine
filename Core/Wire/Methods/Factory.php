<?php

namespace Minds\Core\Wire\Methods;

use Minds\Core\Di\Di;

class Factory
{

    public static function build($method)
    {
        switch (ucfirst($method)) {
          case "Points":
              return Di::_()->get('Wire\Method\Points');
          case "Money":
               return Di::_()->get('Wire\Method\Money');
          case "Tokens":
               return Di::_()->get('Wire\Method\Tokens');
          default:
            throw new \Exception("Method not found");
        }
    }

}
