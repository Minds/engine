<?php
namespace Minds\Core\Notifications\PostSubscriptions\Controllers;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PostSubscriptionsController
{
    public function __construct(
        private PostSubscriptionsService $postSubscriptionsService,
        private EntitiesBuilder $entitiesBuilder,
    ) {

    }

    #[Query]
    #[Logged]
    public function getPostSubscription(
        string $entityGuid,
        #[InjectUser] User $loggedInUser,
    ): PostSubscription {
        $entity = $this->entitiesBuilder->single((int) $entityGuid);

        if (!$entity) {
            throw new NotFoundException("The entity could not be found");
        }

        return $this->postSubscriptionsService
            ->withUser($loggedInUser)
            ->withEntity($entity)
            ->get();
    }

    #[Mutation]
    #[Logged]
    public function updatePostSubscription(
        string $entityGuid,
        PostSubscriptionFrequencyEnum $frequency,
        #[InjectUser] User $loggedInUser,
    ): PostSubscription {
        $entity = $this->entitiesBuilder->single((int) $entityGuid);

        if (!$entity) {
            throw new NotFoundException("The entity could not be found");
        }

        $success = $this->postSubscriptionsService
            ->withUser($loggedInUser)
            ->withEntity($entity)
            ->subscribe($frequency);

        if (!$success) {
            throw new ServerErrorException("There was a problem saving the subscription");
        }

        return $this->postSubscriptionsService
            ->withUser($loggedInUser)
            ->withEntity($entity)
            ->get();
    }
}
