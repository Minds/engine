<?php
namespace Minds\Core\ActivityPub\Exceptions;

use Minds\Exceptions\ServerErrorException;

class RemoteGoneException extends ServerErrorException
{
    protected $code = 410;
    protected $message = "The remote content is gone and can not be fetched";
}
