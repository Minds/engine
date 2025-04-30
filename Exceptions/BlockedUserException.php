<?php
namespace Minds\Exceptions;

use Minds\Core\Router\Exceptions\ForbiddenException;

/**
 * Exception thrown by trying to comment to a blocked user
 */
class BlockedUserException extends ForbiddenException
{
    protected $message = 'The user may have blocked you from interacting with them.';
}
