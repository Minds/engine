<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Controllers;

use Minds\Core\Payments\Checkout\Services\CheckoutService;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\Exception\ApiErrorException;
use Zend\Diactoros\Response\RedirectResponse;

class CheckoutPsrController
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     * @throws ApiErrorException
     */
    public function completeCheckout(ServerRequestInterface $request): RedirectResponse
    {
        $user = $request->getAttribute('_user');
        $this->checkoutService->completeCheckout(
            user: $user,
            stripeCheckoutSessionId: $request->getQueryParams()['session_id']
        );
        return new RedirectResponse('/networks/checkout?confirmed=true');
    }
}
