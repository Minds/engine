<?php
/**
 * Throw when user blocks more users than is allowed
 */
namespace Minds\Core\Security\Block;

use Minds\Exceptions\UserErrorException;

class BlockLimitException extends UserErrorException
{
    /** @var string */
    protected $message = "Block limit exceeded";
}
