<?php
namespace Minds\Core\Subscriptions\Requests;

use Exception;

class SubscriptionRequestAlreadyCompletedException extends Exception
{
    /** @var string */
    protected $message = "A subscription request was accepted/declined but has already been actioned.";
}
