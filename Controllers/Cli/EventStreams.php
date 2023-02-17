<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;

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

        // Bypass the ACL at the CLI level,
        Di::_()->get('Security\ACL')->setIgnore(true);

        $subscriptionClassName = "Minds\\" . $this->getOpt('subscription');

        $subscription = new $subscriptionClassName;

        $topic = $subscription->getTopic();

        $topic->consume($subscription->getSubscriptionId(), function ($event) use ($subscription) {
            return $subscription->consume($event);
        }, $subscription->getTopicRegex());
    }

    public function exec_batch()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Bypass the ACL at the CLI level,
        Di::_()->get('Security\ACL')->setIgnore(true);

        $subscriptionClassName = "Minds\\" . $this->getOpt('subscription');

        $subscription = new $subscriptionClassName;

        $topic = $subscription->getTopic();

        if (!method_exists($topic, 'consumeBatch')) {
            echo "No batch consume method found for the requested topic " . get_class($topic);
            return;
        }

        $topic->consumeBatch(
            subscriptionId: $subscription->getSubscriptionId(),
            callback: function (array $messages) use ($subscription) {
                return $subscription->consumeBatch($messages);
            },
            topicRegex: $subscription->getTopicRegex(),
            batchTotalAmount: $this->getOpt('batch_total_amount'),
            execTimeoutInSeconds: $this->getOpt('exec_timeout_in_seconds')
        );
    }
}
