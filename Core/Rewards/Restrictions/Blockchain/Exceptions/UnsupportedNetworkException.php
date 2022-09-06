<?php
namespace Minds\Core\Rewards\Restrictions\Blockchain\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Thrown when a network is not supported.
 */
class UnsupportedNetworkException extends UserErrorException
{
    /** @var string */
    protected $message = "Network is unsupported";
}
