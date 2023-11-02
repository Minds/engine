<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use GraphQL\Error\UserError;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Types\MultiTenantCustomHostname;
use Minds\Core\MultiTenant\Types\MultiTenantDomain;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class DomainsController
{
    public function __construct(
        private readonly DomainService $domainService
    ) {
    }

    /**
     * @return MultiTenantDomain
     */
    #[Query]
    #[Logged]
    public function getMultiTenantDomain(): MultiTenantDomain
    {
        try {
            return $this->domainService->getDomainDetails();
        } catch (\Exception $e) {
            throw new UserError($e->getMessage());
        }
    }

    /**
     * @param MultiTenantCustomHostname $input
     * @return MultiTenantCustomHostname
     * @throws GuzzleException
     */
    #[Mutation]
    public function createMultiTenantDomain(
        MultiTenantCustomHostname $input
    ): MultiTenantCustomHostname {
        return $this->domainService->setupMultiTenantCustomHostname($input->hostname);
    }
}
