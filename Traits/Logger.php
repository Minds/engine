<?php
/**
 * Logger
 * @author edgebal
 */

namespace Minds\Traits;

use Minds\Core\Di\Di;
use Minds\Core\Log\LoggerContext;

trait Logger
{
    /**
     * @param string $context
     * @return LoggerContext
     */
    protected function logger($context = null)
    {
        if (!$context) {
            $tokens = str_ireplace(['\\Minds\\Core', '\\Minds', '\\'], ' ', '\\' . get_called_class());
            $context = str_replace(' ', '', ucwords(trim($tokens)));
        }

        return Di::_()->get('Log')->get($context);
    }
}
