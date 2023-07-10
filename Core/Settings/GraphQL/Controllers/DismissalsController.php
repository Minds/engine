<?php
declare(strict_types=1);

namespace Minds\Core\Settings\GraphQL\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Core\Settings\GraphQL\Types\Dismissal;
use Minds\Core\Settings\Manager;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * GraphQL controller handling a users dismissals (e.g. when they dismiss an explainer screen).
 */
class DismissalsController
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager = Di::_()->get('Settings\Manager');
    }

    /**
     * Get all of a users dismissals.
     * @return Dismissal[] array of dismissals.
     */
    #[Query]
    #[Logged]
    public function getDismissals(
        #[InjectUser] User $loggedInUser = null
    ): array {
        try {
            return iterator_to_array(
                $this->manager->setUser($loggedInUser)
                    ->getDismissals($loggedInUser)
            );
        } catch(UserSettingsNotFoundException $e) {
            return [];
        }
    }

    /**
     * Get dismissal by key.
     * @param string $key - The key of the dismissal to get.
     * @return ?Dismissal
     */
    #[Query]
    #[Logged]
    public function getDismissalByKey(
        string $key,
        #[InjectUser] User $loggedInUser = null
    ): ?Dismissal {
        try {
            return $this->manager->setUser($loggedInUser)
                ->getDismissalByKey($key);
        } catch(UserSettingsNotFoundException $e) {
            return null;
        }
    }

    /**
     * Dismiss a notice by its key.
     * @param string $key - key of item to dismiss.
     * @return Dismissal
     */
    #[Mutation]
    #[Logged]
    public function dismiss(
        string $key,
        #[InjectUser] User $loggedInUser = null
    ): Dismissal {
        return $this->manager->setUser($loggedInUser)
            ->upsertDismissal($key);
    }
}
