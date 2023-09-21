<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\InferredTags;

use Minds\Cli\Controller as CliController;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\Events\InferredTagEvent;
use Minds\Core\EventStreams\Topics\InferredTagsTopic;
use Minds\Core\Log\Logger;
use Minds\Interfaces\CliControllerInterface;

class Generate extends CliController implements CliControllerInterface
{
    public function __construct(
        private ?Logger $logger = null
    ) {
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @inheritDoc
     */
    public function help($command = null)
    {
        // TODO: Implement help() method.
    }

    /**
     * @inheritDoc
     */
    public function exec(): void
    {
        [
            'activity_urn' => $activity_urn,
            'guid' => $guid,
            'inferred_tags' => $inferredTags
        ] = $this->getAllOpts();


        $this->logger->warning('Validating arguments...');

        if (!$activity_urn || !$guid) {
            $this->logger->error('Missing required arguments');
            exit(1);
        }

        $pulsarTopic = new InferredTagsTopic();
        $event = new InferredTagEvent(
            activityUrn: $activity_urn,
            guid: (int) $guid,
            embedString: '',
            inferredTags: $inferredTags ?: [
                'test_inferred_tags',
                'test2',
            ]
        );

        $this->logger->info('Event created', [
            'activity_urn' => $event->activityUrn,
            'guid' => $event->guid,
            'embed_string' => $event->embedString,
            'inferred_tags' => $event->inferredTags,
        ]);

        $this->logger->warning('Sending event to pulsar');
        $result = $pulsarTopic->send($event);
        $this->logger->warning('Event sent to pulsar', ['result' => $result]);
    }
}
