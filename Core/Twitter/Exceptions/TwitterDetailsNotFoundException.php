<?php

namespace Minds\Core\Twitter\Exceptions;

class TwitterDetailsNotFoundException extends \Minds\Exceptions\UserErrorException
{
    protected $code = 404;

    protected $message = "The requested twitter details were not found";
}
