<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception thrown when a signature mismatch is found.
 */
class SignatureMismatchException extends UserErrorException
{
    /** @var string */
    protected $message = 'Signature mismatch for solution';
}
