<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\CaptionedActivities;

use Minds\Cli\Controller as CliController;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\Events\CaptionedActivityEvent;
use Minds\Core\EventStreams\Topics\CaptionedActivitiesTopic;
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
            'type' => $type,
            'caption' => $caption
        ] = $this->getAllOpts();


        $this->logger->warning('Validating arguments...');

        if (!$activity_urn || !$guid || !$type) {
            $this->logger->error('Missing required arguments');
            exit(1);
        }

        $pulsarTopic = new CaptionedActivitiesTopic();
        $event = (new CaptionedActivityEvent())
            ->setActivityUrn($activity_urn)
            ->setGuid($guid)
            ->setType($type)
            ->setCaption($caption ?: 'test caption');

        $this->logger->warning("", [
            'activity_urn' => $event->getActivityUrn(),
            'guid' => $event->getGuid(),
            'type' => $event->getType(),
            'caption' => $event->getCaption(),
        ]);

        $this->logger->warning('Sending event to pulsar');
        $result = $pulsarTopic->send($event);
        $this->logger->warning('Event sent to pulsar', ['result' => $result]);
    }
}
