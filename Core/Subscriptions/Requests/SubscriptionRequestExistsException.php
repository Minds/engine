<?php
namespace Minds\Core\Subscriptions\Requests;

use Exception;

class SubscriptionRequestExistsException extends Exception
{
    /** @var string */
    protected $message = "A subscription request already exists but tried to be created.";
}
