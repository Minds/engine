<?php
namespace Minds\Core\ActivityPub\Services;

use ActivityPhp\Type\Extended\AbstractActor;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
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
use Minds\Entities\User;

class EmitActivityService
{
    public function __construct(
        protected ActorFactory $actorFactory,
        protected ObjectFactory $objectFactory,
        protected Client $client,
        protected Manager $manager,
        protected EntitiesBuilder $entitiesBuilder,
        protected Logger $logger,
    ) {

    }

    /**
     * Emits the activity to the correct audience
     */
    public function emitActivity(ActivityType $activity, User $actor): void
    {
        // Find a list of all our followers inboxes
        foreach ($this->manager->getInboxesForFollowers($actor->getGuid()) as $inboxUrl) {
            $this->postRequest($inboxUrl, $activity, $actor);
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
     */
    public function emitFollow(FollowType $follow, User $actor): void
    {
        // Get the targets inbox

        $target = $follow->object;

        if (!$target instanceof AbstractActorType) {
            return;
        }

        $inboxUrl = $target->endpoints['sharedInbox'] ?? $target->inbox;

        $this->postRequest($inboxUrl, $follow, $actor);
    }

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
            $this->postRequest($inboxUrl, $like, $actor);
        }
    }

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
            $this->postRequest($inboxUrl, $undo, $actor);
        }
    }

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

        $this->postRequest($inboxUrl, $flag);
    }

    /**
     * Emit Accept event (usually just for a Follow Response)
     */
    public function emitAccept(AcceptType $accept, User $actor): void
    {
        // Get the targets inbox
        if ($accept->object instanceof FollowType) {
            $target = $this->actorFactory->fromUri(JsonLdHelper::getValueOrId($accept->object->actor));
            $inboxUrl = $target->endpoints['sharedInbox'] ?? $target->inbox;

            $this->postRequest($inboxUrl, $accept, $actor);
        } else {
            // Not supported
        }
    }

    private function postRequest(string $inboxUrl, ActivityType $activity, ?User $actor = null): bool
    {
        if (strpos($inboxUrl, $this->manager->getBaseUrl(), 0) === 0) {
            return false;
        }

        $disallowInboxes = [
            'https://freespeechextremist.com/inbox', // Unresponsive
            'https://bae.st/inbox', // Unresponsive
            'https://shitposter.club/inbox', // Unresponsive
            'https://poster.place/inbox', // Unresponsive
        ];

        if (in_array($inboxUrl, $disallowInboxes, true) === true) {
            return false;
        }

        $this->logger->info("POST $inboxUrl: Sending");
        try {
            $privateKey = $actor ? $this->manager->getPrivateKey($actor) : $this->manager->getPrivateKeyByUserGuid(0);
            $response = $this->client
                ->withPrivateKeys([
                    $activity->actor->id . '#main-key' => (string) $privateKey,
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
