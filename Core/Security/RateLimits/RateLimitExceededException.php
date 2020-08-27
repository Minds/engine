<?php
namespace Minds\Core\Security\RateLimits;

use Minds\Exceptions\UserErrorException;

class RateLimitExceededException extends UserErrorException
{
    /** @var string */
    protected $message = "You have exceed the rate limit. Please try again later.";
}
