<?php
namespace Minds\Core\Wire\Paywall;

use Minds\Exceptions\UserErrorException;

class PaywallInvalidCreationInputException extends UserErrorException
{
    /** @var string */
    protected $message = "An invalid input was supplied to a paywalled entity.";
}
