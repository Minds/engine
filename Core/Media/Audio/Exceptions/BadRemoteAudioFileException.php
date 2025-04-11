<?php
namespace Minds\Core\Media\Audio\Exceptions;

use Minds\Exceptions\ServerErrorException;

class BadRemoteAudioFileException extends ServerErrorException
{
    protected $message = "There was a problem fetching the remote audio file";
}
