<?php
namespace Minds\Core\Reports\AutomatedReportStreams;

use Exception;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\AbstractTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Pulsar\Consumer;
use Pulsar\ConsumerOptions;
use Pulsar\Exception\IOException;
use Pulsar\Exception\MessageNotFound;
use Pulsar\Exception\OptionsException;
use Pulsar\Exception\RuntimeException;
use Pulsar\SubscriptionType;

class AdminReportsTopic extends AbstractTopic implements TopicInterface
{
    /**
     * @inheritDoc
     */
    public function send(EventInterface $event): bool
    {
        // NOT IMPLEMENTED
        return false;
    }

    /**
     * @param string $subscriptionId
     * @param callable $callback
     * @param string $topicRegex
     * @throws IOException
     * @throws MessageNotFound
     * @throws OptionsException
     * @throws RuntimeException
     * @throws Exception
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*'): void
    {
        $consumer = $this->getConsumer($subscriptionId, $topicRegex);

        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();

        while (true) {
            $message = $consumer->receive(); // Will hang until received
            try {
                $data = json_decode($message->getPayload(), true);

                if (!$data) {
                    // Upstream bad data
                    $consumer->ack($message);
                    continue;
                }

                $topicName = str_replace("persistent://$tenant/$namespace/", '', $message->getTopic());

                $event = match ($topicName) {
                    Events\ScoreCommentsForSpamEvent::TOPIC_NAME => new Events\ScoreCommentsForSpamEvent($data),
                    Events\AdminReportSpamCommentEvent::TOPIC_NAME => new Events\AdminReportSpamCommentEvent($data),
                    'admin-report-spam-accounts' => new Events\AdminReportSpamAccountEvent($data),
                    'admin-report-token-accounts' => new Events\AdminReportTokenAccountEvent($data),
                };

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->ack($message);
                } else {
                    throw new Exception("Failed to process message");
                }
            } catch (Exception $e) {
                $consumer->nack($message);
            }
        }
    }

    /**
     * @param string $subscriptionId
     * @param string $topicRegex
     * @return Consumer
     * @throws IOException
     * @throws OptionsException
     */
    private function getConsumer(string $subscriptionId, string $topicRegex): Consumer
    {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();

        $config = new ConsumerOptions();
        $config->setSubscriptionType(SubscriptionType::Shared);

        return $this->client()->subscribeWithRegex("persistent://$tenant/$namespace/$topicRegex", $subscriptionId, $config);
    }
}
