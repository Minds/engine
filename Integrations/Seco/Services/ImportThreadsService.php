<?php
namespace Minds\Integrations\Seco\Services;

use Minds\Core\Authentication\Services\RegisterService;
use Minds\Core\Comments\Comment;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\Group;
use RegistrationException;

class ImportThreadsService
{
    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Save $save,
        private readonly RegisterService $registerService,
        private readonly CommentManager $commentManager,
        private readonly GroupMembershipManager $groupMembershipManager,
        private readonly ACL $acl
    ) {

    }

    /**
     * Imports threads, users and comments based off a json input
     */
    public function process(
        User $actorUser,
        User $secoAssistant,
        Group $group,
        array $threads
    ): void {

        if (!$this->groupMembershipManager->getMembership($group, $actorUser)->isOwner()) {
            throw new ForbiddenException("Only group owners can import threads.");
        }

        $this->acl->setIgnore(true);
        $authors = [];

        foreach ($threads as $thread) {
            // Make the post with the seco_assistant channel

            $activity = $this->createActivity(
                group: $group,
                owner: $secoAssistant,
                title: $thread['title'],
                category: $thread['category'],
                message: $thread['summary'],
                date: $thread['date'],
            );

            foreach ($thread['comments'] as $commentData) {
                $authorId = $commentData['author'];
                if (!isset($authors[$authorId])) {
                    $authors[$authorId] = $this->findOrCreateUser($authorId);
                }

                $commentOwner = $authors[$authorId];

                // Join the user to the group, if they are not in it already
                $this->groupMembershipManager->joinGroup(
                    group: $group,
                    user: $commentOwner,
                    membershipLevel: GroupMembershipLevelEnum::MEMBER,
                );

                $comment = new Comment();
                $comment->setEntityGuid($activity->getGuid());
                $comment->setParentGuidL1(0);
                $comment->setParentGuidL2(0);
                $comment->setBody($commentData['content']);
                $comment->setOwnerGuid($commentOwner->getGuid());
                $comment->setTimeCreated(strtotime(trim($commentData['timestamp'], '[]')));
                $comment->setSource(FederatedEntitySourcesEnum::LOCAL);
                $this->commentManager->add($comment, rateLimit: false);
            }
            
        }
    }

    /**
     * Creates the activity post
     */
    private function createActivity(
        Group $group,
        User $owner,
        string $title,
        string $category,
        string $message,
        string $date
    ): Activity {
        $activity = new Activity();
        $activity->title = $title;
        $activity->message = $message;
        $activity->time_created = strtotime($date);


        $activity->setAccessId($group->getGuid());
        $activity->setSource(FederatedEntitySourcesEnum::LOCAL);

        $activity->container_guid = $group->getGuid();
        $activity->owner_guid = $owner->guid;
        $activity->ownerObj = $owner->export();

        $this->save->setEntity($activity)->save();

        return $activity;
    }

    /**
     * Create a user based on a name or find a user based off their name
     * TODO: When the json file has author ids, reference these instead of the username
     */
    private function findOrCreateUser(string $name): User
    {
        $email = 'no-reply@minds.com';

        $preferredUsername = strtolower(str_replace(' ', '', $name));

        if ($foundUser = $this->entitiesBuilder->getByUserByIndex($preferredUsername)) {
            return $foundUser;
        }

        try {
            validate_username($preferredUsername);
        } catch (RegistrationException) {
            // An invalid username was passed. We will try and create one for the user.
            $preferredUsername = substr(md5((string) time()), 0, 8);
        }

        $password = bin2hex(openssl_random_pseudo_bytes(128));
        $user = $this->registerService->register(
            username: $preferredUsername,
            password: $password,
            name: $name,
            email: $email,
            validatePassword: false
        );

        return $user;
    }
}
