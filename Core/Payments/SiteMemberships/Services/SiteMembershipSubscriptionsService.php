<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipErrorEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;

class SiteMembershipSubscriptionsService
{
    public function __construct(
        private readonly SiteMembershipSubscriptionsRepository $siteMembershipSubscriptionsRepository,
        private readonly SiteMembershipReaderService           $siteMembershipReaderService,
        private readonly StripeCheckoutManager                 $stripeCheckoutManager,
        private readonly StripeProductService                  $stripeProductService,
        private readonly StripeCheckoutSessionService          $stripeCheckoutSessionService,
        private readonly Config                                $config
    ) {
    }

    /**
     * @param int $siteMembershipGuid
     * @param User $user
     * @param string $redirectPath
     * @return string
     * @throws ApiErrorException
     * @throws InvalidArgumentException
     * @throws NoSiteMembershipFoundException
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws NoSiteMembershipSubscriptionFoundException
     */
    public function getCheckoutLink(
        int    $siteMembershipGuid,
        User   $user,
        string $redirectPath
    ): string {
        $siteMembershipSubscription = $this->siteMembershipSubscriptionsRepository->getSiteMembershipSubscriptionByMembershipGuid(
            membershipGuid: $siteMembershipGuid,
            user: $user
        );

        if ($siteMembershipSubscription) {
            return $this->config->get('site_url') . ltrim($redirectPath, '/') . "?error=" . SiteMembershipErrorEnum::SUBSCRIPTION_ALREADY_EXISTS->name;
        }

        $siteMembership = $this->siteMembershipReaderService->getSiteMembership($siteMembershipGuid);
        $checkoutSession = $this->stripeCheckoutManager->createSession(
            user: $user,
            mode: $siteMembership->membershipPricingModel === SiteMembershipPricingModelEnum::RECURRING ? CheckoutModeEnum::SUBSCRIPTION : CheckoutModeEnum::PAYMENT,
            successUrl: "api/v3/payments/site-memberships/$siteMembershipGuid/checkout/complete?session_id={CHECKOUT_SESSION_ID}",
            cancelUrl: ltrim($redirectPath, '/'),
            lineItems: $this->prepareLineItems($siteMembership->stripeProductId),
            paymentMethodTypes: [
                'card',
            ],
            metadata: [
                'redirectPath' => $redirectPath,
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
        return [
            [
                'price' => $product->default_price,
                'quantity' => 1,
            ],
        ];
    }

    /**
     * @param string $stripeCheckoutSessionId
     * @param User $user
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
        $redirectPath = $stripeCheckoutSession->metadata['redirectPath'];

        $siteMembership = $this->siteMembershipReaderService->getSiteMembership((int)$siteMembershipGuid);

        $this->siteMembershipSubscriptionsRepository->storeSiteMembershipSubscription(
            user: $user,
            siteMembership: $siteMembership,
            stripeSubscriptionId: $siteMembership->membershipPricingModel === SiteMembershipPricingModelEnum::RECURRING ? $stripeCheckoutSession->subscription : $stripeCheckoutSession->payment_intent
        );

        return $redirectPath;
    }

    /**
     * @param User|null $user
     * @return SiteMembershipSubscription[]
     * @throws ServerErrorException
     */
    public function getSiteMembershipSubscriptions(
        ?User $user = null
    ): array {
        return iterator_to_array($this->siteMembershipSubscriptionsRepository->getSiteMembershipSubscriptions($user));
    }
}
