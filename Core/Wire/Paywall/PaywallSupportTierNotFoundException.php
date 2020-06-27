<?php
namespace Minds\Core\Wire\Paywall;

use Minds\Exceptions\UserErrorException;

class PaywallSupportTierNotFoundException extends UserErrorException
{
    /** @var string */
    protected $message = "The selected tier does not exist or could not be found";
}
