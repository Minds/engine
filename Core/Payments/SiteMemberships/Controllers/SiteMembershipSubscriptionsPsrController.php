<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Zend\Diactoros\Response\RedirectResponse;

class SiteMembershipSubscriptionsPsrController
{
    public function __construct(
        private readonly SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     * @throws NoSiteMembershipFoundException
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     * @throws ApiErrorException
     */
    public function goToSiteMembershipCheckoutLink(ServerRequestInterface $request): RedirectResponse
    {
        $membershipGuid = $request->getAttribute('parameters')['membershipGuid'];
        $redirectUri = $request->getQueryParams()['redirectUrl'] ?? '/memberships';
        $loggedInUser = $request->getAttribute('_user');

        $checkoutLink = $this->siteMembershipSubscriptionsService->getCheckoutLink(
            siteMembershipGuid: (int)$membershipGuid,
            user: $loggedInUser,
            redirectUri: $redirectUri
        );

        return new RedirectResponse(
            uri: $checkoutLink
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     */
    public function completeSiteMembershipPurchase(ServerRequestInterface $request): RedirectResponse
    {
        $stripeCheckoutSessionId = $request->getQueryParams()['session_id'];
        $loggedInUser = $request->getAttribute('_user');
        $redirectUri = $this->siteMembershipSubscriptionsService->completeSiteMembershipCheckout(
            stripeCheckoutSessionId: $stripeCheckoutSessionId,
            user: $loggedInUser
        );
        return new RedirectResponse(
            uri: $redirectUri
        );
    }
}
