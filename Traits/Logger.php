<?php
/**
 * Logger
 * @author edgebal
 */

namespace Minds\Traits;

use Minds\Core\Di\Di;
use Minds\Core\Log\Log;
use Minds\Core\Log\LoggerContext;

trait Logger
{
    /**
     * @param string $context
     * @return LoggerContext
     */
    protected function logger($context = null)
    {
        /** @var Log $logger */
        $logger = Di::_()->get('Log');

        if (!$context) {
            $context = get_called_class();
        }

        return $logger->get($logger::buildContext($context));
    }
}
