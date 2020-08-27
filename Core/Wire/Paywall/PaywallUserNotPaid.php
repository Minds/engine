<?php
namespace Minds\Core\Wire\Paywall;

use Minds\Exceptions\UserErrorException;

class PaywallUserNotPaid extends UserErrorException
{
    /** @var string */
    protected $message = "Please ensure you have sent the correct amount to unlock this post";
}
