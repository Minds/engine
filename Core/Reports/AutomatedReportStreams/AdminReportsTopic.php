<?php
namespace Minds\Core\Reports\AutomatedReportStreams;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\AbstractTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;

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
    public function consume(
        string $subscriptionId,
        callable $callback,
        string $topicRegex = '*',
        bool $isBatch = false,
        int $batchTotalAmount = 1,
        int $execTimeoutInSeconds = 30,
        ?callable $onBatchConsumed = null
    ): void {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topicRegex = $topicRegex;

        $config = new ConsumerConfiguration();
        $config->setConsumerType(Consumer::ConsumerShared);
        // $config->setSchema(SchemaType::JSON, "SpamComment", '{
        //     "type": "record",
        //     "name": "SpamComment",
        //     "fields": [
        //         {"name": "comment_guid", "type": "long" },
        //         {"name": "owner_guid", "type": "long" },
        //         {"name": "entity_guid", "type": ["null", "long" ] },
        //         {"name": "parent_guid_l1", "type": ["null", "long" ] },
        //         {"name": "parent_guid_l2", "type": ["null", "long" ] },
        //         {"name": "parent_guid_l3", "type": ["null", "long" ] },
        //         {"name": "time_created", "type": "long" },
        //         {"name": "spam_predict", "type": "double" },
        //         {"name": "activity_views", "type": "int" },
        //         {"name": "last_engagement", "type": "long" },
        //         {"name": "score", "type": "double" }
        //     ]
        // }',[]);

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
                    Events\ScoreCommentsForSpamEvent::TOPIC_NAME => new Events\ScoreCommentsForSpamEvent($data),
                    Events\AdminReportSpamCommentEvent::TOPIC_NAME => new Events\AdminReportSpamCommentEvent($data),
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
