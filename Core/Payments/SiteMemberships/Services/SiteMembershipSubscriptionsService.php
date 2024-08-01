<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use DateTime;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipErrorEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipService;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
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
        private readonly HasActiveSiteMembershipCacheService   $hasActiveSiteMembershipCacheService,
        private readonly StripeCheckoutManager                 $stripeCheckoutManager,
        private readonly StripeProductService                  $stripeProductService,
        private readonly StripeCheckoutSessionService          $stripeCheckoutSessionService,
        private readonly Config                                $config,
        private readonly GroupMembershipService                $groupMembershipService,
        private readonly EntitiesBuilder                       $entitiesBuilder,
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
        
        if ($siteMembership->isExternal && $siteMembership->purchaseUrl) {
            return $siteMembership->purchaseUrl;
        }

        $checkoutSession = $this->stripeCheckoutManager->createSession(
            user: $user,
            mode: $siteMembership->membershipPricingModel === SiteMembershipPricingModelEnum::RECURRING ? CheckoutModeEnum::SUBSCRIPTION : CheckoutModeEnum::PAYMENT,
            successUrl: "api/v3/payments/site-memberships/$siteMembershipGuid/checkout/complete?session_id={CHECKOUT_SESSION_ID}",
            cancelUrl: ltrim($redirectPath, '/'),
            lineItems: $this->prepareLineItems($siteMembership->stripeProductId),
            paymentMethodTypes: [
                'card'
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

        $this->addSiteMembershipSubscription(
            new SiteMembershipSubscriptionDTO(
                user: $user,
                siteMembership: $siteMembership,
                stripeSubscriptionId: $siteMembership->membershipPricingModel === SiteMembershipPricingModelEnum::RECURRING ? $stripeCheckoutSession->subscription : $stripeCheckoutSession->payment_intent
            )
        );
        
        return $redirectPath;
    }

    /**
     * Add a site membership subscription to the datastore and run any other processes
     * such as joining the relevant groups
     */
    public function addSiteMembershipSubscription(SiteMembershipSubscriptionDTO $siteMembershipSubscription): bool
    {
        $success = $this->siteMembershipSubscriptionsRepository->storeSiteMembershipSubscription(
            $siteMembershipSubscription
        );

        if ($success) {
            $this->joinGroups($siteMembershipSubscription->siteMembership, $siteMembershipSubscription->user);
        }

        $this->hasActiveSiteMembershipCacheService->delete(
            (int) $siteMembershipSubscription->user->getGuid()
        );

        return $success;
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

    /**
     * @param int|null $tenantId
     * @return iterable<SiteMembershipSubscription>
     * @throws ServerErrorException
     */
    public function getAllSiteMemberships(
        ?int $tenantId = null
    ): iterable {
        return $this->siteMembershipSubscriptionsRepository->getAllSiteMembershipSubscriptions($tenantId);
    }

    /**
     * @param string $stripeSubscriptionId
     * @return SiteMembershipSubscription
     * @throws NoSiteMembershipSubscriptionFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembershipSubscriptionByStripeSubscriptionId(
        string $stripeSubscriptionId
    ): SiteMembershipSubscription {
        return $this->siteMembershipSubscriptionsRepository->getSiteMembershipSubscriptionByStripeSubscriptionId($stripeSubscriptionId);
    }

    /**
     * Whether the user has an active site membership subscription.
     * @param User $user - the user to check for an active site membership subscription.
     * @return bool - whether the user has an active site membership subscription.
     */
    public function hasActiveSiteMembershipSubscription(User $user): bool
    {
        $cachedValue = $this->hasActiveSiteMembershipCacheService->get((int) $user->getGuid());
        if ($cachedValue !== null) {
            return $cachedValue;
        }

        $lastExpiringSubscription = $this->getLastExpiringSiteMembershipSubscription($user);
        $hasActiveSiteMembership = $lastExpiringSubscription !== null;

        $this->hasActiveSiteMembershipCacheService->set(
            (int) $user->getGuid(),
            $hasActiveSiteMembership,
            $hasActiveSiteMembership ? ($lastExpiringSubscription->validToTimestamp - time()) : null
        );

        return $hasActiveSiteMembership;
    }

    /**
     * @param string $stripeSubscriptionId
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @param int|null $userGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function renewSiteMembershipSubscription(
        string $stripeSubscriptionId,
        int $startTimestamp,
        int $endTimestamp,
        ?int $userGuid = null
    ): bool {
        $success = $this->siteMembershipSubscriptionsRepository->renewSiteMembershipSubscription($stripeSubscriptionId, $startTimestamp, $endTimestamp);

        if ($success && $userGuid) {
            $this->hasActiveSiteMembershipCacheService->set(
                $userGuid,
                true,
                $endTimestamp - time()
            );
        }

        return $success;
    }

    /*
     * Return a list of site memberships that are missing group assignmwents
     */
    public function syncOutOfSyncSiteMemberships()
    {
        $siteMembershipSubscriptions = $this->siteMembershipSubscriptionsRepository->getOutOfSyncSiteMemberships();

        foreach ($siteMembershipSubscriptions as $siteMembershipSubscription) {
            $user = $this->entitiesBuilder->single($siteMembershipSubscription->userGuid);
            if (!$user instanceof User) {
                continue;
            }

            $siteMembership = $this->siteMembershipReaderService->getSiteMembership($siteMembershipSubscription->membershipGuid);
            $this->joinGroups($siteMembership, $user);
        }
    }

    /**
     * Gets the users last expiring site membership subscription.
     * @param User $user - the user to get the last expiring site membership subscription for.
     * @return SiteMembershipSubscription|null - the last expiring site membership subscription,
     * or null if the user has none.
     */
    private function getLastExpiringSiteMembershipSubscription(User $user): ?SiteMembershipSubscription
    {
        $siteMembershipSubscriptions = $this->getSiteMembershipSubscriptions($user);
        $lastExpiringSubscription = null;

        foreach ($siteMembershipSubscriptions as $siteMembershipSubscription) {
            if (
                $lastExpiringSubscription === null ||
                $siteMembershipSubscription->validToTimestamp > $lastExpiringSubscription->validToTimestamp
            ) {
                $lastExpiringSubscription = $siteMembershipSubscription;
            }
        }
        
        return $lastExpiringSubscription;
    }

    /**
     * Join groups associated with a membership
     */
    private function joinGroups(SiteMembership $siteMembership, User $user): void
    {
        foreach ($siteMembership->getGroups() as $groupNode) {
            $group = $groupNode->getEntity();
            // Check if there is already a membership. If they are already 'at least a member', then skip.
            try {
                $groupMembership = $this->groupMembershipService->getMembership(group: $group, user: $user);
            
                if ($groupMembership->siteMembershipGuid) {
                    throw new NotFoundException(); // Allow
                }

                if ($groupMembership->membershipLevel->value > GroupMembershipLevelEnum::REQUESTED->value) {
                    continue; // If they already have a membership that is not a request, do not allow
                }
            } catch (NotFoundException) {
                // This is what we want
            }

            $this->groupMembershipService->joinGroup($group, $user, GroupMembershipLevelEnum::MEMBER, $siteMembership->membershipGuid);
        }
    }
}
