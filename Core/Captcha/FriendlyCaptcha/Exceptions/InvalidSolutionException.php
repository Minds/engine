<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception thrown when proposed solution is invalid.
 */
class InvalidSolutionException extends UserErrorException
{
    /** @var string */
    protected $message = 'Invalid CAPTCHA solution';
}
