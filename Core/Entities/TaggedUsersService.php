<?php
namespace Minds\Core\Entities;

use Minds\Common\Regex;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Entities\User;

class TaggedUsersService
{
    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly ACL $acl,
    ) {
        
    }

    public function getUsersFromText(string $text, User $loggedInUser = null, int $limit = 5): array
    {
        if (preg_match_all(Regex::AT, $text, $matches)) {
            $usernames = $matches[1];
            $users = [];

            foreach ($usernames as $username) {
                $user = $this->entitiesBuilder->getByUserByIndex(strtolower($username));

                if ($user instanceof User && $this->acl->interact($user, $loggedInUser)) {
                    $users[] = $user;
                }

                //limit of tags notifications: 5
                if (count($users) >= $limit) {
                    break;
                }
            }

            return $users;
        } else {
            return [];
        }
    }
}
