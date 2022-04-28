<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Exceptions;

use Minds\Exceptions\ServerErrorException;

/**
 * Exception thrown when there is some misconfiguration in the server.
 */
class MisconfigurationException extends ServerErrorException
{
    /** @var string */
    protected $message = "Misconfiguration issue in friendly captcha";
}
