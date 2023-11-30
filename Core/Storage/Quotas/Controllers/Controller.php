<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas\Controllers;

use Minds\Core\Log\Logger;
use Minds\Core\Storage\Quotas\Manager;
use Minds\Core\Storage\Quotas\Types\AssetConnection;
use Minds\Core\Storage\Quotas\Types\QuotaDetails;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Controller
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return QuotaDetails
     * @throws ServerErrorException
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getTenantQuotaUsage(
        #[InjectUser] User $loggedInUser
    ): QuotaDetails {
        return $this->manager->getTenantQuotaUsage();
    }

    /**
     * @param int $first
     * @param string|null $after
     * @param string|null $before
     * @return AssetConnection
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getTenantAssets(
        #[InjectUser] User $loggedInUser,
        int $first = 12,
        ?string $after = null,
        ?string $before = null,
    ): AssetConnection {
        if ($after && $before) {
            throw new GraphQLException('Cannot use both "after" and "before" cursors. Only one cursor should be provided');
        }
        return $this->manager->getTenantAssets(
            first: $first,
            cursor: $after ?? $before
        );
    }

    /**
     * @return QuotaDetails
     * @throws ServerErrorException
     */
    #[Query]
    #[Logged]
    public function getUserQuotaUsage(
        #[InjectUser] User $loggedInUser
    ): QuotaDetails {
        return $this->manager->getUserQuotaUsage($loggedInUser->get('guid'));
    }

    /**
     * @param int $first
     * @param string|null $after
     * @param string|null $before
     * @return AssetConnection
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    #[Query]
    #[Logged]
    public function getUserAssets(
        #[InjectUser] User $loggedInUser,
        int $first = 12,
        ?string $after = null,
        ?string $before = null,
    ): AssetConnection {
        if ($after && $before) {
            throw new GraphQLException('Cannot use both "after" and "before" cursors. Only one cursor should be provided');
        }

        return $this->manager->getUserAssets(
            userId: $loggedInUser->get('guid'),
            first: $first,
            cursor: $after ?? $before
        );
    }
}
