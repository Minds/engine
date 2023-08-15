<?php
namespace Minds\Core\ActivityPub\Services;

use Minds\Common\Regex;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class ProcessActorService
{
    protected PersonType $actor;

    public function __construct(
        protected Manager $manager,
        protected ActorFactory $actorFactory,
        protected ACL $acl,
        protected Save $saveAction,
    ) {
        
    }

    public function withActor(PersonType $actor): ProcessActorService
    {
        $instance = clone $this;
        $instance->actor = $actor;
        return $instance;
    }

    /**
     * Builds the actor from just a username
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

    public function process(): ?User
    {
        $className = get_class($this->actor);
        
        switch ($className) {
            case PersonType::class:

                $user = $this->manager->getEntityFromUri($this->actor->id);
                if ($user) {
                    // Nothing to do, the user already exists
                    return $user;
                }

                $username = $this->actor->preferredUsername . '@' . JsonLdHelper::getDomainFromUri($this->actor->id);
                $email = 'activitypub-imported@minds.com';
                $password = openssl_random_pseudo_bytes(128);

                // Check for username collisions.
                if (check_user_index_to_guid(strtolower($username))) {
                    // Do not continue if there is one (at least for now)
                    return null;
                }

                // Create the user
                $user = register_user($username, $password, $username, $email, validatePassword: false, isActivityPub: true);

                $user->setName($this->actor->name);
                $user->setBriefDescription($this->actor->summary);

                // Set the source as being activitypub
                $user->setSource('activitypub');

                $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
                $this->saveAction->setEntity($user)->save();
                $this->acl->setIgnore($ia); // Reset ACL state

                $this->manager->addActor($this->actor, $user);

                return $user;
                break;
        }

        return null;
    }

}
