<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;

class SiteMembershipSubscriptionsService
{
    public function __construct(
        private readonly SiteMembershipSubscriptionsRepository $siteMembershipSubscriptionsRepository,
        private readonly SiteMembershipReaderService           $siteMembershipReaderService,
        private readonly StripeCheckoutManager                 $stripeCheckoutManager,
        private readonly StripeProductService                  $stripeProductService,
        private readonly StripeProductPriceService             $stripeProductPriceService,
        private readonly StripeCheckoutSessionService          $stripeCheckoutSessionService,
    ) {
    }

    /**
     * @param int $siteMembershipGuid
     * @param User $user
     * @param string $redirectUri
     * @return string
     * @throws ApiErrorException
     * @throws InvalidArgumentException
     * @throws NoSiteMembershipFoundException
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getCheckoutLink(
        int    $siteMembershipGuid,
        User   $user,
        string $redirectUri
    ): string {
        $siteMembership = $this->siteMembershipReaderService->getSiteMembership($siteMembershipGuid);
        $checkoutSession = $this->stripeCheckoutManager->createSession(
            user: $user,
            mode: CheckoutModeEnum::SUBSCRIPTION,
            successUrl: "api/v3/payments/site-memberships/$siteMembershipGuid/checkout/complete?session_id={CHECKOUT_SESSION_ID}",
            cancelUrl: ltrim($redirectUri, '/'),
            lineItems: $this->prepareLineItems($siteMembership->stripeProductId),
            paymentMethodTypes: [
                'card',
                'us_bank_account',
            ],
            metadata: [
                'redirectUri' => $redirectUri,
                'siteMembershipGuid' => (string)$siteMembershipGuid,
            ]
        );

        return $checkoutSession->url;
    }

    /**
     * @param string $productId
     * @return array[]
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     * @throws ApiErrorException
     */
    private function prepareLineItems(string $productId): array
    {
        $product = $this->stripeProductService->getProductById($productId);
        $price = $this->stripeProductPriceService->getPriceDetailsById($product->default_price);
        return [
            [
                'price' => $price->id,
                'quantity' => 1,
            ],
        ];
    }

    /**
     * @param string $stripeCheckoutSessionId
     * @return string
     * @throws ApiErrorException
     * @throws NoSiteMembershipFoundException
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function completeSiteMembershipCheckout(string $stripeCheckoutSessionId, User $user): string
    {
        $stripeCheckoutSession = $this->stripeCheckoutSessionService->retrieveCheckoutSession(
            sessionId: $stripeCheckoutSessionId
        );

        $siteMembershipGuid = $stripeCheckoutSession->metadata['siteMembershipGuid'];
        $redirectUri = $stripeCheckoutSession->metadata['redirectUri'];

        $this->siteMembershipSubscriptionsRepository->storeSiteMembershipSubscription(
            user: $user,
            siteMembership: $this->siteMembershipReaderService->getSiteMembership((int)$siteMembershipGuid),
            stripeSubscriptionId: $stripeCheckoutSession->subscription
        );

        return $redirectUri;
    }
}
