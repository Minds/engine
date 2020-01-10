<?php
namespace Minds\Core\Subscriptions;

class TooManySubscriptionsException extends \Exception
{
    protected $message = "Subscribe to over 5000 channels";
}
