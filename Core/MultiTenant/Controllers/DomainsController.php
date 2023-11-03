<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use GraphQL\Error\UserError;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Types\CustomHostname;
use Minds\Core\MultiTenant\Types\MultiTenantDomain;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

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
            return $this->domainService->getCustomHostname();
        } catch (\Exception $e) {
            throw new UserError($e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     */
    #[Mutation]
    public function createMultiTenantDomain(
        string $hostname
    ): MultiTenantDomain {

        if (!filter_var($hostname, FILTER_VALIDATE_DOMAIN)) {
            throw new GraphQLException("Invalid hostname provided", 400, null, "Validation", ['field' => 'hostname']);
        }

        try {
            if (!!$this->domainService->getDomainDetails()) {
                return $this->domainService->updateCustomHostname($hostname);
            }
        } catch (\Exception $e) {
         
        }

        return $this->domainService->setupCustomHostname($hostname);
    }
}
