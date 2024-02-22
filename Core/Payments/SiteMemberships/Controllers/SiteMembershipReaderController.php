<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Annotations\Query;

class SiteMembershipReaderController
{
    public function __construct(
        private readonly SiteMembershipReaderService $siteMembershipReaderService
    ) {
    }

    /**
     * @return SiteMembership[]
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    #[Query]
    public function siteMemberships(): array
    {
        return $this->siteMembershipReaderService->getSiteMemberships();
    }

    /**
     * @param string $membershipGuid
     * @return SiteMembership
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws NoSiteMembershipFoundException
     * @throws ApiErrorException
     */
    #[Query]
    public function siteMembership(
        string $membershipGuid
    ): SiteMembership {
        return $this->siteMembershipReaderService->getSiteMembership((int)$membershipGuid);
    }
}
