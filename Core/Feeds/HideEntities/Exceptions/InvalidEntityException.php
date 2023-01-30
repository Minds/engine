<?php
namespace Minds\Core\Feeds\HideEntities\Exceptions;

use Minds\Exceptions\UserErrorException;

class InvalidEntityException extends UserErrorException
{
    protected $code = 400; // User error

    protected $message = "You may only hide activity posts at this time.";
}
