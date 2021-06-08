<?php
namespace Minds\Core\Subscriptions;

use Minds\Exceptions\UserErrorException;

class TooManySubscriptionsException extends UserErrorException
{
    protected $message = "You have reached the maximum allowed subscriptions.";
}
