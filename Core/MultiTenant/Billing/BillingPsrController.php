<?php
namespace Minds\Core\MultiTenant\Billing;

use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
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
 
        $checkoutUrl = $this->service->createExternalCheckoutLink('networks:' . $plan, $period);

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
}
