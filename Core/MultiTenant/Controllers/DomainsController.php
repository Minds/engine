<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Types\CustomHostname;
use Minds\Core\MultiTenant\Types\Domain;
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
     * @return Domain
     */
    #[Query]
    #[Logged]
    public function getDomain(): Domain
    {
        return $this->domainService->getDomainDetails();
    }

    /**
     * @param CustomHostname $input
     * @return CustomHostname
     * @throws GuzzleException
     */
    #[Mutation]
    public function createCustomDomain(
        CustomHostname $input
    ): CustomHostname
    {
        return $this->domainService->setupCustomHostname($input->hostname);
    }
}
