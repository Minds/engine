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
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\CaptionedActivitiesTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\StopEventException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Pulsar subscription for captioned activity events
 */
class CaptionedActivityEventStreamSubscription implements SubscriptionInterface
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
        return 'captioned-activity-subscription';
    }

    public function getTopic(): TopicInterface
    {
        return new CaptionedActivitiesTopic();
    }

    public function getTopicRegex(): string
    {
        return 'captioned-activities';
    }

    /**
     * @param EventInterface $event
     * @return bool
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof CaptionedActivityEvent) {
            return true; // Acknowledge message so that it does not get held up in the message queue
        }

        if ($event->getType() === "activity") {
            return true;
        }

        if ($this->cache->get("captioned-activity-{$event->getActivityUrn()}")) {
            $this->logger->info("Skipping captioned activity {$event->getActivityUrn()} as it is already being processed");
            return false;
        }

        try {

            if ($event->getCaption()) {
                $this->updateActivity($event, $event->getCaption());
            }

            return true;
        } catch (NotFoundException $e) {
            $this->logger->info("Skipping captioned activity {$event->getActivityUrn()} as it was not found");
        } catch (Exception $e) {
            $this->logger->info(
                "An issue was encountered whilst processing activity captions for activity {$event->getActivityUrn()}",
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
    private function updateActivity(CaptionedActivityEvent $event, string $caption): void
    {
        $this->cache->set("captioned-activity-{$event->getActivityUrn()}", true);

        try {
            $this->processImageEntity($event, $caption);
            $this->processActivity($event, $caption);
        } catch (\Exception $e) {
            $this->cache->delete("captioned-activity-{$event->getActivityUrn()}");
            // Rethrow
            throw $e;
        }
    }

    /**
     * @param CaptionedActivityEvent $event
     * @param string $caption
     * @return void
     * @throws NotFoundException
     * @throws StopEventException
     * @throws UnverifiedEmailException
     */
    private function processImageEntity(CaptionedActivityEvent $event, string $caption): void
    {
        /**
         * @var Image $imageEntity
         */
        $imageEntity = $this->entitiesBuilder->single($event->getGuid());
        if (!$imageEntity) {
            // Entity not found
            throw new NotFoundException("Image {$event->getGuid()} not found");
        }

        $mutatedImageEntity = new EntityMutation($imageEntity);
        $mutatedImageEntity->setAutoCaption($caption);

        $this->saveAction->setEntity($mutatedImageEntity->getMutatedEntity())->save();
    }

    /**
     * @param CaptionedActivityEvent $event
     * @param string $caption
     * @return void
     * @throws NotFoundException
     * @throws StopEventException
     * @throws UnverifiedEmailException
     */
    private function processActivity(CaptionedActivityEvent $event, string $caption): void
    {
        /**
         * @var Activity $activity
         */
        $activity = $this->entitiesResolver->setOpts([
            'cache' => false
        ])->single(new Urn($event->getActivityUrn()));
        if (!$activity) {
            throw new NotFoundException("Activity {$event->getActivityUrn()} not found");
        }

        $mutatedImageEntity = new EntityMutation($activity);
        $mutatedImageEntity->setAutoCaption($activity->getAutoCaption() . " $caption");

        $this->saveAction->setEntity($mutatedImageEntity->getMutatedEntity())->save();
    }
}
