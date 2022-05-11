<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception thrown when a solution has already been seen.
 */
class SolutionAlreadySeenException extends UserErrorException
{
    /** @var string */
    protected $message = 'Solution already seen';
}
