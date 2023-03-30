<?php
declare(strict_types=1);

namespace Minds\Exceptions;

/**
 * Thrown when a prohibited domain is provided.
 */
class ProhibitedDomainException extends UserErrorException
{
    protected $code = 403;
    protected $message = "Sorry, you included a reference to a domain name linked to spam";
}
