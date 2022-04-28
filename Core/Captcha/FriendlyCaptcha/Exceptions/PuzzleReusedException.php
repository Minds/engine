<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception thrown when puzzle has been re-used.
 */
class PuzzleReusedException extends UserErrorException
{
    /** @var string */
    protected $message = 'CAPTCHA puzzle already used';
}
