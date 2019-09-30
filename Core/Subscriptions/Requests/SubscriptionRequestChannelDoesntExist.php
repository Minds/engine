<?php
namespace Minds\Core\Subscriptions\Requests;

use Exception;

class SubscriptionRequestChannelDoesntExist extends Exception
{
    /** @var string */
    protected $message = "A subscription request was made to a channel that doesnt exist";
}
