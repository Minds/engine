<?php
namespace Minds\Core\MultiTenant\Billing\Controllers;

use Minds\Core\MultiTenant\Billing\BillingService;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;

class BillingPsrController
{
    public function __construct(
        private BillingService $service
    ) {
        
    }

    /**
     * A non 'minds' user purchases a new network
     */
    public function externalCheckout(ServerRequestInterface $request): RedirectResponse
    {
        $plan = $request->getQueryParams()['plan'] ?? null;

        if (!$plan) {
            throw new UserErrorException("A plan must be provided");
        }

        $period = $request->getQueryParams()['period'] ?? null;

        if ($period) {
            $period = constant(CheckoutTimePeriodEnum::class . '::' . strtoupper($period));
        } else {
            throw new UserErrorException("A billing period must be provided");
        }
 
        $checkoutUrl = $this->service->createExternalCheckoutLink(TenantPlanEnum::fromString($plan), $period);

        return new RedirectResponse($checkoutUrl);
    }

    /**
     * Initiates a trial checkout process for a non-Minds user.
     * @param ServerRequestInterface $request - The incoming server request.
     * @return RedirectResponse - A redirect to the Stripe checkout URL.
     */
    public function externalTrialCheckout(ServerRequestInterface $request): RedirectResponse
    {
        $checkoutUrl = $this->service->createExternalTrialCheckoutLink(
            plan: TenantPlanEnum::TEAM,
            timePeriod: CheckoutTimePeriodEnum::MONTHLY
        );
        return new RedirectResponse($checkoutUrl);
    }

    /**
     * Stripe will redirect here after a new network is purchased (non minds users only)
     * The user will be redirected back to the networks.minds.com site in order to capture the session
     * and track conversions better
     */
    public function externalCallback(ServerRequestInterface $request): RedirectResponse
    {
        $checkoutSessionId = $request->getQueryParams()['session_id'];
        $loginUrl = $this->service->onSuccessfulCheckout($checkoutSessionId);

        return new RedirectResponse($loginUrl);
    }

    /**
    * Stripe will redirect here after a new network trial is started (non minds users only)
    * The user will be redirected back to the networks.minds.com site in order to capture the session
    * and track conversions better.
    * @param ServerRequestInterface $request - The incoming server request.
    * @return RedirectResponse - A redirect to the auto-login URL.
    */
    public function externalTrialCallback(ServerRequestInterface $request): RedirectResponse
    {
        $checkoutSessionId = $request->getQueryParams()['session_id'];
        $loginUrl = $this->service->onSuccessfulTrialCheckout($checkoutSessionId);
        return new RedirectResponse($loginUrl);
    }

    /**
     * If a customer doesn't have a stripe subscription, this endpoint can be used to trigger an upgrade
     */
    public function upgradeCheckout(ServerRequestInterface $request): RedirectResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $plan = $request->getQueryParams()['plan'] ?? null;

        if (!$plan) {
            throw new UserErrorException("A plan must be provided");
        }

        $period = $request->getQueryParams()['period'] ?? null;

        if ($period) {
            $period = constant(CheckoutTimePeriodEnum::class . '::' . strtoupper($period));
        } else {
            throw new UserErrorException("A billing period must be provided");
        }
 
        $checkoutUrl = $this->service->createUpgradeCheckoutLink(TenantPlanEnum::fromString($plan), $period, $loggedInUser);

        return new RedirectResponse($checkoutUrl);
    }

    /**
     * Stripe will redirect here after a new network is upgraded
     * The user will be redirected back to the networks.minds.com site in order to capture the session
     * and track conversions better
     */
    public function upgradeCallback(ServerRequestInterface $request): HtmlResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $checkoutSessionId = $request->getQueryParams()['session_id'];
        $this->service->onSuccessfulUpgradeCheckout($checkoutSessionId, $loggedInUser);

        return new HtmlResponse(
            <<<HTML
<script>window.close();</script>
<p>Please close this window/tab.</p>
HTML
        );
    }
}
