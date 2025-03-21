<?php

namespace Minds\Core\ActivityPub\Services;

use Minds\Common\Regex;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Types\Actor\ApplicationType;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\Authentication\Services\RegisterService;
use Minds\Core\Channels\AvatarService;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Security\ACL;
use Minds\Core\Webfinger\WebfingerService;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class ProcessActorService
{
    protected AbstractActorType $actor;

    public function __construct(
        protected Manager $manager,
        protected ActorFactory $actorFactory,
        protected ACL $acl,
        protected Save $saveAction,
        protected AvatarService $avatarService,
        protected RegisterService $registerService,
        protected WebfingerService $webfingerService,
    ) {
    }

    /**
     * Constructs the actor from just a uri.
     */
    public function withActorUri(string $uri): ProcessActorService
    {
        $actor = $this->actorFactory->fromUri($uri);

        return $this->withActor($actor);
    }

    /**
     * Constructs the actor from an actor type.
     */
    public function withActor(AbstractActorType $actor): ProcessActorService
    {
        $instance = clone $this;
        $instance->actor = $actor;

        return $instance;
    }

    /**
     * Builds the actor from just a username.
     */
    public function withUsername(string $username): ProcessActorService
    {
        preg_match(Regex::EMAIL, $username, $matches);

        if (count($matches)) {
            try {
                $actor = $this->actorFactory->fromWebfinger($username);

                return $this->withActor($actor);
            } catch (\Exception $e) {
                // Do not continue if any exception is found
            }
        }

        throw new NotFoundException();
    }

    public function process(bool $update = true): ?User
    {
        $className = get_class($this->actor);

        switch ($className) {
            case PersonType::class:
            case ApplicationType::class:
                $user = $this->manager->getEntityFromUri(JsonLdHelper::getValueOrId($this->actor));
                if ($user) {
                    if ($update) {
                        // The user already exists, lets resync
                        $this->updateUser($user);
                    }

                    return $user;
                }

                $username = $this->actor->preferredUsername.'@'.JsonLdHelper::getDomainFromUri($this->actor->id);

                // Get the desired webfinger from a uri
                try {
                    $desiredUsername = str_replace('acct:', '', $this->webfingerService->get('acct:'.$username)['subject'] ?? '');

                    // If the username is different than what we expected, we request the webfinger again
                    // to ensure that it resolves back to the intended uri
                    if (strtolower($desiredUsername) !== strtolower($username)) {
                        if ($this->manager->getUriFromUsername($desiredUsername, revalidateWebfinger: true) === JsonLdHelper::getValueOrId($this->actor)) {
                            // Validated desiredUsername correctly points to the same uri
                            $username = $desiredUsername;
                        }
                    }
                } catch (\Exception $e) {
                    // Could not do webfinger resolution, continuing with initial from uri
                }

                $email = 'activitypub-imported@minds.com';
                $password = bin2hex(openssl_random_pseudo_bytes(128));

                // Check for username collisions.
                if (check_user_index_to_guid(strtolower($username))) {
                    // Do not continue if there is one (at least for now)
                    return null;
                }

                $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf

                if (isset($this->actor->url)) {
                    $canonicalUrl = $this->actor->url;
                } else {
                    $canonicalUrl = JsonLdHelper::getValueOrId($this->actor);
                }

                // Create the user
                $user = $this->registerService->register($username, $password, $username, $email, validatePassword: false, isActivityPub: true, canonicalUrl: $canonicalUrl);

                $this->acl->setIgnore($ia); // Reset ACL state

                $user = $this->updateUser($user);

                return $user;
                break;
        }

        return null;
    }

    private function updateUser(User $user): User
    {
        if (isset($this->actor->name)) {
            $user->setName($this->actor->name);
        }

        if (isset($this->actor->summary)) {
            $user->setBriefDescription($this->actor->summary);
        }

        try {
            // Try to pull in an avatar, only if it differs
            if (isset($this->actor->icon) && $this->manager->getActorIconUrl($this->actor) !== $this->actor->icon->url) {
                $this->avatarService
                    ->withUser($user)
                    ->createFromUrl($this->actor->icon->url);

                $user->icontime = time();
            }
        } catch (\Exception $e) {
        }

        // Save the user
        $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
        $this->saveAction->setEntity($user)->save();
        $this->acl->setIgnore($ia); // Reset ACL state

        // Sync the actor to database (updated inboxes, icon reference etc)
        $this->manager->addActor($this->actor, $user);

        return $user;
    }
}
