<?php
namespace Minds\Core\ActivityPub\Exceptions;

use Minds\Exceptions\UserErrorException;

class MissingEntityException extends UserErrorException
{
    protected $code = 410;
    protected $message = "The entity could not be found on Minds.";
}
