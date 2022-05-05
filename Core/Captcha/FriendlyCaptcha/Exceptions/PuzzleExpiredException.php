<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception thrown when puzzle is expired.
 */
class PuzzleExpiredException extends UserErrorException
{
    /** @var string */
    protected $message = 'CAPTCHA puzzle has expired';
}
