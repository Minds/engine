<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsManagementService;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\Exception\ApiErrorException;
use Zend\Diactoros\Response\RedirectResponse;

class SiteMembershipSubscriptionsManagementPsrController
{
    public function __construct(
        private readonly SiteMembershipSubscriptionsManagementService $siteMembershipSubscriptionsManagementService
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     * @throws ApiErrorException
     * @throws NoSiteMembershipSubscriptionFoundException
     * @throws ServerErrorException
     * @throws UserErrorException
     */
    public function goToManageSiteMembershipSubscriptionLink(ServerRequestInterface $request): RedirectResponse
    {
        $siteMembershipSubscriptionId = $request->getAttribute('parameters')['siteMembershipSubscriptionId'];
        $redirectPath = $request->getQueryParams()['redirectPath'];

        $link = $this->siteMembershipSubscriptionsManagementService->generateManageSiteMembershipSubscriptionLink(
            siteMembershipSubscriptionId: (int)$siteMembershipSubscriptionId,
            redirectPath: $redirectPath
        );

        return new RedirectResponse(uri: $link);
    }

    /**
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     * @throws ServerErrorException
     */
    public function completeSiteMembershipSubscriptionCancellation(ServerRequestInterface $request): RedirectResponse
    {
        $siteMembershipSubscriptionId = $request->getAttribute('parameters')['siteMembershipSubscriptionId'];
        $redirectPath = $request->getQueryParams()['redirectPath'];

        $this->siteMembershipSubscriptionsManagementService->completeSiteMembershipCancellation((int)$siteMembershipSubscriptionId);

        return new RedirectResponse(uri: $redirectPath);
    }
}
