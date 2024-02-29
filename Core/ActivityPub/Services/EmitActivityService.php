<?php

namespace Minds\Core\ActivityPub\Services;

use ActivityPhp\Type\Extended\AbstractActor;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Helpers\CircuitStatusEnum;
use Minds\Core\ActivityPub\Helpers\EmitterCircuitBreaker;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Activity\AcceptType;
use Minds\Core\ActivityPub\Types\Activity\FlagType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\LikeType;
use Minds\Core\ActivityPub\Types\Activity\UndoType;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Psr\SimpleCache\InvalidArgumentException;

class EmitActivityService
{
    public function __construct(
        protected ActorFactory                 $actorFactory,
        protected ObjectFactory                $objectFactory,
        protected Client                       $client,
        protected Manager                      $manager,
        protected EntitiesBuilder              $entitiesBuilder,
        protected Logger                       $logger,
        private readonly EmitterCircuitBreaker $circuitBreaker
    ) {

    }

    /**
     * Emits the activity to the correct audience
     * @throws InvalidArgumentException
     */
    public function emitActivity(ActivityType $activity, User $actor): void
    {
        // Find a list of all our followers inboxes
        foreach ($this->manager->getInboxesForFollowers($actor->getGuid()) as $inboxUrl) {
            if (($cbStatus = $this->circuitBreaker->evaluateCircuit($inboxUrl)) !== CircuitStatusEnum::HEALTHY) {
                $this->logger->warning("Emit Activity: Circuit Breaker tripped for $inboxUrl. Circuit Breaker status is $cbStatus->name");
                continue;
            }
            if (!$this->postRequest($inboxUrl, $activity, $actor)) {
                $this->circuitBreaker->tripCircuit($inboxUrl);
            }
        }

        // If there are any mentions or additional cc's, also send to those
        foreach ($activity->object->cc as $cc) {
            try {
                $ccActor = $this->actorFactory->fromUri($cc);
            } catch (\Exception $e) {
                continue;
            }

            $this->postRequest($ccActor->inbox, $activity, $actor);
        }
    }

    /**
     * Emit Accept event (usually just for a Follow Response)
     * @throws InvalidArgumentException
     */
    public function emitFollow(FollowType $follow, User $actor): void
    {
        // Get the targets inbox

        $target = $follow->object;

        if (!$target instanceof AbstractActorType) {
            return;
        }

        $inboxUrl = $target->endpoints['sharedInbox'] ?? $target->inbox;
        if (($cbStatus = $this->circuitBreaker->evaluateCircuit($inboxUrl)) !== CircuitStatusEnum::HEALTHY) {
            $this->logger->warning("Emit Activity: Circuit Breaker tripped for $inboxUrl. Circuit Breaker status is $cbStatus->name");
            return;
        }

        if (!$this->postRequest($inboxUrl, $follow, $actor)) {
            $this->circuitBreaker->tripCircuit($inboxUrl);
        }
    }

    /**
     * @throws UserErrorException
     * @throws NotFoundException
     * @throws ForbiddenException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    public function emitLike(LikeType $like, User $actor): void
    {
        // Get the targets inbox
        $target = $this->objectFactory->fromUri($like->object->attributedTo);
        if (!$target instanceof AbstractActorType) {
            $this->logger->info("Emit Like: Failed - target is not an actor");
            return;
        }

        $inboxUrls = iterator_to_array($this->manager->getInboxesForFollowers($actor->getGuid()));

        if (!$this->manager->isLocalUri($like->object->attributedTo)) {
            // TODO: Dedup
            $inboxUrls[] = $target->endpoints['sharedInbox'] ?? $target->inbox;
        }

        foreach ($inboxUrls as $inboxUrl) {
            if (($cbStatus = $this->circuitBreaker->evaluateCircuit($inboxUrl)) !== CircuitStatusEnum::HEALTHY) {
                $this->logger->warning("Emit Activity: Circuit Breaker tripped for $inboxUrl. Circuit Breaker status is $cbStatus->name");
                continue;
            }

            if (!$this->postRequest($inboxUrl, $like, $actor)) {
                $this->circuitBreaker->tripCircuit($inboxUrl);
            }
        }
    }

    /**
     * @throws UserErrorException
     * @throws NotFoundException
     * @throws ForbiddenException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    public function emitUndoLike(LikeType $like, User $actor, string $attributedTo): void
    {
        // Get the targets inbox
        $target = $this->objectFactory->fromUri($attributedTo);
        if (!$target instanceof AbstractActorType) {
            $this->logger->info("Emit Undo Like: Failed - target is not an actor");
            return;
        }

        $undo = new UndoType();
        $undo->id = $this->manager->getTransientId();
        $undo->actor = $like->actor;
        $undo->object = $like;

        $inboxUrls = iterator_to_array($this->manager->getInboxesForFollowers($actor->getGuid()));

        if (!$this->manager->isLocalUri($attributedTo)) {
            // TODO: Dedup
            $inboxUrls[] = $target->endpoints['sharedInbox'] ?? $target->inbox;
        }

        foreach ($inboxUrls as $inboxUrl) {
            if (($cbStatus = $this->circuitBreaker->evaluateCircuit($inboxUrl)) !== CircuitStatusEnum::HEALTHY) {
                $this->logger->warning("Emit Activity: Circuit Breaker tripped for $inboxUrl. Circuit Breaker status is $cbStatus->name");
                continue;
            }

            if (!$this->postRequest($inboxUrl, $undo, $actor)) {
                $this->circuitBreaker->tripCircuit($inboxUrl);
            }
        }
    }

    /**
     * @throws UserErrorException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    public function emitFlag(FlagType $flag, string $attributedTo): void
    {
        if ($this->manager->isLocalUri($attributedTo)) {
            $this->logger->info("Emit Flag: Skipped - target is local");
            return;
        }

        $target = $this->objectFactory->fromUri($attributedTo);
        if (!$target instanceof AbstractActorType) {
            $this->logger->info("Emit Undo Like: Failed - target is not an actor");
            return;
        }

        $inboxUrl = $target->endpoints['sharedInbox'] ?? $target->inbox;
        if (($cbStatus = $this->circuitBreaker->evaluateCircuit($inboxUrl)) !== CircuitStatusEnum::HEALTHY) {
            $this->logger->warning("Emit Activity: Circuit Breaker tripped for $inboxUrl. Circuit Breaker status is $cbStatus->name");
            return;
        }

        if (!$this->postRequest($inboxUrl, $flag)) {
            $this->circuitBreaker->tripCircuit($inboxUrl);
        }
    }

    /**
     * Emit Accept event (usually just for a Follow Response)
     * @param AcceptType $accept
     * @param User $actor
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UserErrorException
     */
    public function emitAccept(AcceptType $accept, User $actor): void
    {
        // Get the targets inbox
        if ($accept->object instanceof FollowType) {
            $target = $this->actorFactory->fromUri(JsonLdHelper::getValueOrId($accept->object->actor));
            $inboxUrl = $target->endpoints['sharedInbox'] ?? $target->inbox;
            if (($cbStatus = $this->circuitBreaker->evaluateCircuit($inboxUrl)) !== CircuitStatusEnum::HEALTHY) {
                $this->logger->warning("Emit Activity: Circuit Breaker tripped for $inboxUrl. Circuit Breaker status is $cbStatus->name");
                return;
            }

            if (!$this->postRequest($inboxUrl, $accept, $actor)) {
                $this->circuitBreaker->tripCircuit($inboxUrl);
            }
        } else {
            // Not supported
        }
    }

    private function postRequest(string $inboxUrl, ActivityType $activity, ?User $actor = null): bool
    {
        if (strpos($inboxUrl, $this->manager->getBaseUrl(), 0) === 0) {
            return false;
        }

        $this->logger->info("POST $inboxUrl: Sending");
        try {
            $privateKey = $actor ? $this->manager->getPrivateKey($actor) : $this->manager->getPrivateKeyByUserGuid(0);
            $response = $this->client
                ->withPrivateKeys([
                    $activity->actor->id . '#main-key' => (string)$privateKey,
                ])
                ->request('POST', $inboxUrl, [
                    ...$activity->getContextExport(),
                    ...$activity->export()
                ]);
            $this->logger->info("POST $inboxUrl: Delivered");
            return true;
        } catch (\Exception $e) {
            $this->logger->info("POST $inboxUrl: Failed {$e->getMessage()}");
            return false;
        }
    }
}
