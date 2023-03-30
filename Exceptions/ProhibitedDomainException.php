<?php

namespace Minds\Exceptions;

class ProhibitedDomainException extends UserErrorException
{
    protected $code = 403;
    protected $message = "Sorry, you included a reference to a domain name linked to spam";
}
