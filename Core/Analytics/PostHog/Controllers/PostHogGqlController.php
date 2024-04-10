<?php
namespace Minds\Core\Analytics\PostHog\Controllers;

use Minds\Core\Analytics\PostHog\Models\PostHogPerson;
use Minds\Core\Analytics\PostHog\PostHogPersonService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PostHogGqlController
{
    public function __construct(
        private PostHogPersonService $postHogPersonService,
    ) {
        
    }

    #[Query]
    #[Logged]
    public function getPostHogPerson(
        #[InjectUser] User $loggedInUser = null,
    ): PostHogPerson {
        return $this->postHogPersonService->getPerson($loggedInUser->getGuid());
    }

    #[Mutation]
    #[Logged]
    public function deletePostHogPerson(
        #[InjectUser] User $loggedInUser = null,
    ): bool {
        return $this->postHogPersonService->deletePerson($loggedInUser->getGuid());
    }
}
