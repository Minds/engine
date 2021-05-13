<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Interfaces;
use Minds\Core\Di\Di;

class EventStreams extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
        Di::_()->get('Config')
          ->set('min_log_level', 'INFO');
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $subscriptionClassName = "Minds\\" . $this->getOpt('subscription');

        $subscription = new $subscriptionClassName;
        
        $topic = $subscription->getTopic();

        $topic->consume($subscription->getSubscriptionId(), function ($event) use ($subscription) {
            return $subscription->consume($event);
        }, $subscription->getTopicRegex());
    }
}
