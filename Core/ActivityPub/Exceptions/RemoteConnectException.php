<?php
namespace Minds\Core\ActivityPub\Exceptions;

use Minds\Exceptions\ServerErrorException;

class RemoteConnectException extends ServerErrorException
{
    protected $message = "Unable to connect to the remote";
}
