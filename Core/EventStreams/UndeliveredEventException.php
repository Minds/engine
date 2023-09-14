<?php
namespace Minds\Core\EventStreams;

use Minds\Exceptions\ServerErrorException;

class UndeliveredEventException extends ServerErrorException
{
    public $message = "Unable to deliver stream event";
}
