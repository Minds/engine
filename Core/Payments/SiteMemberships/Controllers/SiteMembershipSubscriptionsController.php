<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Query;

class SiteMembershipSubscriptionsController
{
    public function __construct(
        private readonly SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        private readonly EventsDispatcher $eventsDispatcher,
    ) {
    }

    /**
     * @return SiteMembershipSubscription[]
     * @throws ServerErrorException
     */
    #[Query]
    public function siteMembershipSubscriptions(
        #[InjectUser] ?User $loggedInUser = null
    ): array {
        if (!$loggedInUser) {
            return [];
        }

        $this->eventsDispatcher->trigger('site-membership:revalidate', 'all', [
            'user' => $loggedInUser
        ]);

        return $this->siteMembershipSubscriptionsService->getSiteMembershipSubscriptions($loggedInUser);
    }
}
