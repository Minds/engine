<?php

declare(strict_types=1);

namespace Minds\Core\Payments;

use Minds\Api\Exportable;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Controller for Payments Module.
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null,
        private ?Config $config = null
    ) {
        $this->manager ??= new Manager();
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Get payments for logged in user.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    //    #[OA\Get(
    //        path: '/api/v3/payments',
    //        parameters: [
    //            new OA\Parameter(
    //                name: "offset",
    //                in: "query",
    //                required: false,
    //                schema: new OA\Schema(type: 'string')
    //            ),
    //            new OA\Parameter(
    //                name: "limit",
    //                in: "query",
    //                required: false,
    //                schema: new OA\Schema(type: 'integer')
    //            )
    //        ],
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 400, description: "Bad Request"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //        ]
    //    )]
    public function getPayments(ServerRequest $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute("_user");
        $queryParams = $request->getQueryParams();

        $opts = new GetPaymentsOpts();

        if ($startingAfter = $queryParams['offset'] ?? false) {
            $opts->setStartingAfter($startingAfter);
        }

        if ($limit = $queryParams['limit'] ?? false) {
            $opts->setLimit((int) $limit);
        }

        $response = $this->manager
            ->setUserGuid($loggedInUser->getGuid())
            ->getPayments($opts);

        return new JsonResponse([
            'status' => 'success',
            'data' => Exportable::_($response['data']) ?? [],
            'has_more' => $response['has_more'] ?? null
        ]);
    }

    /**
     * Redirect a user to receipt. If the user does not have permission,
     * will redirect to base site url rather than throwing an error and
     * leaving them on a white screen.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    //    #[OA\Get(
    //        path: '/api/v3/payments/:paymentId',
    //        parameters: [
    //            new OA\Parameter(
    //                name: "paymentId",
    //                in: "path",
    //                required: true,
    //                schema: new OA\Schema(type: 'string')
    //            )
    //        ],
    //        responses: [
    //            new OA\Response(response: 302, description: "Redirect to receipt"),
    //            new OA\Response(response: 401, description: "Unauthorized")
    //        ]
    //    )]
    public function redirectToReceipt(ServerRequest $request): RedirectResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $paymentId = $request->getAttribute('parameters')['paymentId'] ?? '';

        try {
            $payment = $this->manager->getPaymentById($paymentId);

            if (
                $payment &&
                $payment->getSender() &&
                $payment->getSender()->getGuid() === $loggedInUser->getGuid() &&
                $link = $payment->getReceiptUrl() ?? false
            ) {
                return new RedirectResponse($link);
            }
        } catch (\Exception $e) {
            // Do nothing, we want to redirect back to site rather
            // than leaving the user on a blank screen.
        }

        return new RedirectResponse($this->config->get('site_url'));
    }
}
