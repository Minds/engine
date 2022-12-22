<?php
namespace Minds\Core\Feeds\HideEntities\Exceptions;

use Minds\Exceptions\UserErrorException;

class TooManyHiddenException extends UserErrorException
{
    protected $code = 429; // too many requests

    protected $message = "You have hidden too many posts in the last 24 hours";
}
