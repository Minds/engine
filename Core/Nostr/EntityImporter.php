<?php

namespace Minds\Core\Nostr;

use Minds\Common\Access;
use Minds\Common\Urn;
use Minds\Core\Channels\AvatarService;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Security\ACL;
use Minds\Core\Feeds;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * This class imports NostrEvents to Minds Entities
 */
class EntityImporter
{
    public function __construct(
        protected ?Manager $manager = null,
        protected ?Save $saveAction = null,
        protected ?ACL $acl = null,
        protected ?Feeds\Activity\Manager $activityManager = null,
        protected ?AvatarService $avatarService = null
    ) {
        $this->manager ??= new Manager();
        $this->saveAction ??= new Save();
        $this->acl ??= Di::_()->get('Security\ACL');
        $this->activityManager ??= Di::_()->get('Feeds\Activity\Manager');
        $this->avatarService ??= Di::_()->get('Channels\AvatarService');
    }

    /**
     * Will create a user on Minds.
     * Note: Metadata is only available following a KIND:0 event
     * Username to be in format such as:
     *     minds.com/nostr_86f0689
     *     @nostr_86f0689
     * @param string $pubKey
     * @return User
     */
    public function createUserFromPublicKey(string $pubKey): User
    {
        $username = 'nostr_' . substr($pubKey, 0, 7);
        $email = 'nostr-imported@minds.com';
        $password = openssl_random_pseudo_bytes(128);

        // Check for username collisions. Increment if another username if found
        if (check_user_index_to_guid(strtolower($username))) {
            $username .= '_' . rand(0, 99);
        }

        // Create the user
        $user = register_user($username, $password, $username, $email, validatePassword: false);


        // Set the source as being nostr
        $user->setSource('nostr');

        $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
        $this->saveAction->setEntity($user)->save();
        $this->acl->setIgnore($ia); // Reset ACL state

        // Now add correlation
        $this->manager->addNostrUser($user, $pubKey);

        return $user;
    }

    /**
     * Logic for importing a NostrEvent to a Minds Entity
     * @param NostrEvent $nostrEvent
     * @return void
     */
    public function onNostrEvent(NostrEvent $nostrEvent): void
    {
        // Is the public key whitelisted?
        if (!$this->manager->isOnWhitelist($nostrEvent->getPubKey())) {
            throw new UserErrorException("Pubkey is not whitelisted.", 403);
        }

        // Is there already a nostr id for this public key?
        if (!$user = $this->manager->getUserByPublicKey($nostrEvent->getPubKey())) {
            $user = $this->createUserFromPublicKey($nostrEvent->getPubKey());
        }

        // Possible that a user is disabled, banned etc. Double check we got a real user back.
        if (!$user instanceof User) {
            throw new UserErrorException("Invalid User. This could be a server error, or the user may be unavailable.");
        }

        if (!$this->manager->verifyEvent(json_encode(['EVENT', $nostrEvent->export()], JSON_UNESCAPED_SLASHES))) {
            throw new UserErrorException("Invalid event. Signature verification failed.", 403);
        }

        // Begin transaction
        $this->manager->beginTransaction();

        try {
            // Save the event to the database
            $this->manager->addEvent($nostrEvent);

            // Save replies
            $replies =  array_filter($nostrEvent->getTags(), fn (array $tag): bool => $tag[0] == "e");

            if (count($replies) > 0) {
                $this->manager->addReply($nostrEvent->getId(), $replies);
            }

            // Save mentions
            $mentions =  array_filter($nostrEvent->getTags(), fn (array $tag): bool => $tag[0] == "p");

            if (count($mentions) > 0) {
                $this->manager->addMention($nostrEvent->getId(), $mentions);
            }

            switch ($nostrEvent->getKind()) {
                case NostrEvent::EVENT_KIND_0: // set_metadata

                    $metadata = json_decode($nostrEvent->getContent(), true);

                    $user->setName($metadata['name'] ?? $nostrEvent->getPubKey());
                    $user->setBriefDescription($metadata['about'] ?? '');

                    if ($metadata['picture'] ?? null) {
                        try {
                            $this->avatarService
                                ->withUser($user)
                                ->createFromUrl($metadata['picture']);
                        } catch (\Exception $e) {
                            // Do not block just because image import failed
                        }
                    }

                    $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
                    $this->saveAction->setEntity($user)->save();
                    $this->acl->setIgnore($ia); // Reset ACL state

                    // Commit
                    $this->manager->commit();

                    break;
                case NostrEvent::EVENT_KIND_1: // text_note

                    // First, check that we have not already imported this activity,
                    if ($this->manager->getActivityFromNostrId($nostrEvent->getId())) {
                        break; // Already imported, so don't import again
                    }

                    $activity = new Activity();
                    // As per TwitterSync, this needs cleaning up
                    $activity->container_guid = $user->guid;
                    $activity->owner_guid = $user->guid;
                    $activity->ownerObj = $user->export();
                    //
                    $activity->setMessage($nostrEvent->getContent());
                    $activity->setAccessId(Access::PUBLIC);
                    $activity->setSource('nostr');

                    $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
                    $this->activityManager->add($activity);
                    $this->acl->setIgnore($ia); // Reset ACL state

                    // Add this activity to `nostr_kind_1_to_activity_guid`
                    $this->manager->addActivityToNostrId($activity, $nostrEvent->getId());

                    // Commit
                    $this->manager->commit();

                    break;
                case NostrEvent::EVENT_KIND_2: // recommend_server
                case NostrEvent::EVENT_KIND_9: // delete
                    // If the event contains e tags
                    if (count($replies) > 0) {
                        $events = array_map(fn ($tag): string => $tag[1], $replies);

                        // First, validate the public key matches for each event
                        foreach ($this->manager->getNostrEvents(['ids' => $events ]) as $event) {
                            if ($nostrEvent->getPubKey() != $event->getPubKey()) {
                                throw new UserErrorException("Invalid delete request. Public keys do not match!");
                            }
                        }

                        // Then, delete the events from Vitess
                        $this->manager->deleteNostrEvents($events);

                        // Then, delete activities
                        foreach ($this->manager->getActivitiesFromNostrId($events) as $activity) {
                            $this->activityManager->delete($activity);
                        }

                        // Finally, delete the event->actvitiy mapping
                        $this->manager->deleteActivityToNostrId($events);
                    }

                    // Commit
                    $this->manager->commit();
                    break;
                default:
                    // Commit
                    $this->manager->commit();

                    break;
            }
        } catch (\Exception $e) {
            // On error, roll back and rethrow exception
            $this->manager->rollBack();
            throw $e;
        }
    }
}
