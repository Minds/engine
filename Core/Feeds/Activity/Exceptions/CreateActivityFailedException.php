<?php

namespace Minds\Core\Feeds\Activity\Exceptions;

use Minds\Exceptions\UserErrorException;

class CreateActivityFailedException extends UserErrorException
{
    protected $code = 500;

    protected $message = "An error was encountered whilst creating the Activity post";
}
