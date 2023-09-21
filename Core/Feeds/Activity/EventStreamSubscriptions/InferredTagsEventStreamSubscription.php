<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\Activity\EventStreamSubscriptions;

use Exception;
use Minds\Common\EntityMutation;
use Minds\Common\Urn;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\CaptionedActivityEvent;
use Minds\Core\EventStreams\Events\InferredTagEvent;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\InferredTagsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities\Activity;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\StopEventException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Pulsar subscription for captioned activity events
 */
class InferredTagsEventStreamSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?EntitiesResolver $entitiesResolver = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Save $saveAction = null,
        private ?PsrWrapper       $cache = null,
        private ?Logger           $logger = null
    ) {
        $this->entitiesResolver ??= new EntitiesResolver();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->saveAction ??= new Save();
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
        $this->logger ??= Di::_()->get('Logger');
    }

    public function getSubscriptionId(): string
    {
        return 'inferred-tags-subscription';
    }

    public function getTopic(): TopicInterface
    {
        return new InferredTagsTopic();
    }

    public function getTopicRegex(): string
    {
        return 'inferred-tags';
    }

    /**
     * @param EventInterface $event
     * @return bool
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof InferredTagEvent) {
            return true; // Acknowledge message so that it does not get held up in the message queue
        }

        if ($this->cache->get("inferred-tags-{$event->activityUrn}")) {
            $this->logger->info("Skipping captioned activity {$event->activityUrn} as it is already being processed");
            return false;
        }

        try {
            $this->updateActivity($event);

            return true;
        } catch (Exception $e) {
            $this->logger->info(
                "An issue was encountered whilst processing activity captions for activity {$event->activityUrn}",
                [
                    "exception" => [
                        "message" => $e->getMessage(),
                        "trace" => $e->getTrace(),
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                        "code" => $e->getCode()
                    ]
                ]
            );
            return false;
        }
    }

    /**
     * @param CaptionedActivityEvent $event
     * @param string $caption
     * @return void
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws StopEventException
     * @throws UnverifiedEmailException
     */
    private function updateActivity(InferredTagEvent $event): void
    {
        $this->cache->set("captioned-activity-{$event->activityUrn}", true);

        /**
         * @var Activity $activity
         */
        $activity = $this->entitiesResolver->setOpts([
            'cache' => false
        ])->single(new Urn($event->activityUrn));
        if (!$activity) {
            throw new NotFoundException("Activity {$event->activityUrn} not found");
        }

        $mutatedImageEntity = new EntityMutation($activity);
        $mutatedImageEntity->setInferredTags($event->inferredTags);

        $this->saveAction->setEntity($mutatedImageEntity->getMutatedEntity())->save();

        $this->cache->delete("captioned-activity-{$event->activityUrn}");
    }
}
