<?php
namespace Minds\Core\Reports\AutomatedReportStreams;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\AbstractTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

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
     * @inheritDoc
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*'): void
    {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topicRegex = 'admin-report-' . $topicRegex;
   
        $config = new ConsumerConfiguration();
        $config->setConsumerType(Consumer::ConsumerShared);

        $consumer = $this->client()->subscribeWithRegex("persistent://$tenant/$namespace/$topicRegex", $subscriptionId, $config);
    
        while (true) {
            try {
                $message = $consumer->receive(); // Will hang until received
                $data = json_decode($message->getDataAsString(), true);

                if (!$data) {
                    // Upstream bad data
                    $consumer->acknowledge($message);
                    continue;
                }

                $topicName = str_replace("persistent://$tenant/$namespace/", '', $message->getMessageId()->getTopicName());

                $event = match ($topicName) {
                    'admin-report-spam-comments' => new Events\AdminReportSpamCommentEvent($data),
                    'admin-report-spam-accounts' => new Events\AdminReportSpamAccountEvent($data),
                    'admin-report-token-accounts' => new Events\AdminReportTokenAccountEvent($data),
                };

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                } else {
                    throw new \Exception("Failed to process message");
                }
            } catch (\Exception $e) {
                $consumer->negativeAcknowledge($message);
            }
        }
    }
}
