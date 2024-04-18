<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Webhooks\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipsRenewalsService;
use Minds\Exceptions\ServerErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class SiteMembershipWebhooksPsrController
{
    public function __construct(
        private readonly SiteMembershipsRenewalsService $siteMembershipsRenewalsService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ServerErrorException
     */
    public function processSubscriptionRenewal(ServerRequestInterface $request): JsonResponse
    {
        $payload = $request->getBody()->getContents();
        $signature = $request->getHeader("HTTP_STRIPE_SIGNATURE")[0] ?? null;

        $this->siteMembershipsRenewalsService->processSubscriptionRenewalEvent(
            $payload,
            $signature
        );
        return new JsonResponse(
            data: [],
            status: 200
        );
    }
}
