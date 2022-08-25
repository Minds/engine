<?php
namespace Minds\Core\Rewards\Restrictions\Blockchain;

use Minds\Exceptions\UserErrorException;

/**
 * Thrown when an address is restricted.
 */
class RestrictedException extends UserErrorException
{
    /** @var string */
    protected $message = "Your address is restricted";
}
