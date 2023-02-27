<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\BatchSubscriptionInterface;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Interfaces;

class EventStreams extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private const DEFAULT_BATCH_AMOUNT = 10000;
    private const DEFAULT_EXEC_TIMEOUT_IN_SECONDS = 30;

    public function __construct()
    {
        Di::_()->get('Config')
          ->set('min_log_level', 'INFO');
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    /**
     * @return void
     */
    public function exec(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Bypass the ACL at the CLI level,
        Di::_()->get('Security\ACL')->setIgnore(true);

        $subscriptionClassName = "Minds\\" . $this->getOpt('subscription');

        $subscription = new $subscriptionClassName;

        $topic = $subscription->getTopic();

        $batchTotalAmount = 1;
        $execTimeoutInSeconds = null;

        $isBatch = (bool) $this->getOpt('batch') ?? false;

        if ($isBatch) {
            $batchTotalAmount = $this->getOpt('batch_total_amount') ?? self::DEFAULT_BATCH_AMOUNT;
            $execTimeoutInSeconds = $this->getOpt('exec_timeout_in_seconds') ?? self::DEFAULT_EXEC_TIMEOUT_IN_SECONDS;
        }

        $topic->consume(
            subscriptionId: $subscription->getSubscriptionId(),
            callback:
                $isBatch ?
                    $this->batchConsume($subscription)
                :
                    $this->singleConsume($subscription),
            topicRegex: $subscription->getTopicRegex(),
            isBatch: $isBatch,
            batchTotalAmount: $batchTotalAmount,
            execTimeoutInSeconds: $execTimeoutInSeconds,
            onBatchConsumed: $this->onBatchConsumed($isBatch, $subscription)
        );
    }

    private function singleConsume(SubscriptionInterface $subscription): callable
    {
        return function (EventInterface $event) use ($subscription): bool {
            return $subscription->consume($event);
        };
    }

    private function batchConsume(BatchSubscriptionInterface $subscription): callable
    {
        return function (array $messages) use ($subscription): bool {
            return $subscription->consumeBatch($messages);
        };
    }

    private function onBatchConsumed(bool $isBatch, BatchSubscriptionInterface $subscription): ?callable
    {
        return match ($isBatch) {
            true => function () use ($subscription): void {
                $subscription->commitChanges();
            },
            default => null
        };
    }
}
