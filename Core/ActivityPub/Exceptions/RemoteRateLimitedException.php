<?php
namespace Minds\Core\ActivityPub\Exceptions;

use Minds\Exceptions\ServerErrorException;

class RemoteRateLimitedException extends ServerErrorException
{
    protected $code = 429;
    protected $message = "The remote server returned a rate limit reached response";
}
