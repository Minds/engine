<?php

namespace Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Exceptions\ServerErrorException;

/**
 * Thrown when balance sync was attempted on an excluded user.
 */
class SyncExcludedUserException extends ServerErrorException
{
    /** @var string - default error message */
    protected $message = "Attempted to sync balance of excluded user";
}
