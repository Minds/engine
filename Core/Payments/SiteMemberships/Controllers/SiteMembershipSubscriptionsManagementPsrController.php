<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsManagementService;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
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
     */
    public function goToManageSiteMembershipSubscriptionLink(ServerRequestInterface $request): RedirectResponse
    {
        $siteMembershipSubscriptionId = $request->getAttribute('parameters')['siteMembershipSubscriptionId'];
        $redirectUri = $request->getQueryParams()['redirectUri'];

        $link = $this->siteMembershipSubscriptionsManagementService->generateManageSiteMembershipSubscriptionLink(
            siteMembershipSubscriptionId: (int)$siteMembershipSubscriptionId,
            redirectUri: $redirectUri
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
        $redirectUri = $request->getQueryParams()['redirectUri'];

        $this->siteMembershipSubscriptionsManagementService->cancelSiteMembershipCancellation((int)$siteMembershipSubscriptionId);

        return new RedirectResponse(uri: $redirectUri);
    }
}
