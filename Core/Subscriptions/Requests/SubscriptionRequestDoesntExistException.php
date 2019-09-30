<?php
namespace Minds\Core\Subscriptions\Requests;

use Exception;

class SubscriptionRequestDoesntExistException extends Exception
{
    /** @var string */
    protected $message = "A subscription request currently does not exist, but was interacted with";
}
