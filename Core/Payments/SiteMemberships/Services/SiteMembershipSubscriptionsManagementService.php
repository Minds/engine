<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipErrorEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\Stripe\CustomerPortal\Enums\CustomerPortalFlowTypeEnum;
use Minds\Core\Payments\Stripe\CustomerPortal\Helpers\CustomerPortalFlowConfiguration;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService as StripeCustomerPortalService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService as StripeSubscriptionsService;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;

class SiteMembershipSubscriptionsManagementService
{
    public function __construct(
        private readonly SiteMembershipSubscriptionsRepository $siteMembershipSubscriptionsRepository,
        private readonly StripeSubscriptionsService            $stripeSubscriptionsService,
        private readonly StripeCustomerPortalService           $stripeCustomerPortalService,
        private readonly Config                                $config
    ) {
    }

    /**
     * @param int $siteMembershipSubscriptionId
     * @param string $redirectPath
     * @return string
     * @throws ApiErrorException
     * @throws NoSiteMembershipSubscriptionFoundException
     * @throws ServerErrorException
     * @throws UserErrorException
     */
    public function generateManageSiteMembershipSubscriptionLink(int $siteMembershipSubscriptionId, string $redirectPath): string
    {
        $siteMembershipSubscription = $this->siteMembershipSubscriptionsRepository->getSiteMembershipSubscriptionById($siteMembershipSubscriptionId);

        if (str_starts_with($siteMembershipSubscription->stripeSubscriptionId, 'sub_') && !$siteMembershipSubscription->autoRenew) {
            return $this->config->get('site_url') . ltrim($redirectPath, '/') . "?error=" . SiteMembershipErrorEnum::SUBSCRIPTION_ALREADY_CANCELLED->name;
        }

        $stripeSubscription = $this->stripeSubscriptionsService->retrieveSubscription($siteMembershipSubscription->stripeSubscriptionId);

        return $this->stripeCustomerPortalService->createCustomerPortalSession(
            stripeCustomerId: $stripeSubscription->customer,
            redirectUrl: $this->config->get('site_url') . ltrim($redirectPath, '/'),
            flowData: (new CustomerPortalFlowConfiguration(
                flowType: CustomerPortalFlowTypeEnum::SUBSCRIPTION_CANCEL,
                redirectUrl: $this->config->get('site_url') . "api/v3/payments/site-memberships/subscriptions/$siteMembershipSubscription->membershipSubscriptionId/manage/cancel?redirectPath=$redirectPath",
                subscriptionId: $stripeSubscription->id,
            ))->toArray()
        );
    }

    /**
     * @param int $siteMembershipSubscriptionId
     * @return void
     * @throws ServerErrorException
     */
    public function completeSiteMembershipCancellation(int $siteMembershipSubscriptionId): void
    {
        $this->siteMembershipSubscriptionsRepository->setSiteMembershipSubscriptionAutoRenew(
            siteMembershipSubscriptionId: $siteMembershipSubscriptionId,
            autoRenew: false
        );
    }
}
