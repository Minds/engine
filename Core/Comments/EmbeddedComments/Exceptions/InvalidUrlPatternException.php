<?php
namespace Minds\Core\Comments\EmbeddedComments\Exceptions;

use Minds\Exceptions\UserErrorException;

class InvalidUrlPatternException extends UserErrorException
{
    protected $message = "The provided url does not match the configured url pattern";
}
