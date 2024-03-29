<?php
namespace Minds\Core\Payments\InAppPurchases;

use Minds\Core\Di\Di;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardPaymentFailedException;
use Minds\Core\Payments\InAppPurchases\Apple\AppleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\StopEventException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(private ?Manager $manager = null)
    {
        $this->manager ??= Di::_()->get(Manager::class);
    }

    /**
     * Acknowledge a purchase
     * /api/v3/payments/iap/subscription/acknowledge
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function acknowledgeSubscription(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $payload = $request->getParsedBody();

        if (!$payload) {
            throw new UserErrorException("A payload must sent");
        }

        $service = match ($payload['service'] ?? "") {
            Manager::GOOGLE => GoogleInAppPurchasesClient::class,
            Manager::APPLE => AppleInAppPurchasesClient::class,
            default => throw new UserErrorException("Invalid 'service'. Must be 'google' or 'apple'"),
        };

        $subscriptionId = $payload['subscriptionId'] ?? "";
        $purchaseToken = $payload['purchaseToken'] ?? "";

        $inAppPurchase = new InAppPurchase(
            source: $service,
            purchaseToken: $purchaseToken,
            subscriptionId: $subscriptionId,
            iosTransactionPayload: $payload['verificationResultIOS'] ?? null,
            user: $user
        );

        $this->manager->acknowledgeSubscription($inAppPurchase);

        return new JsonResponse([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws NotImplementedException
     * @throws UserErrorException
     * @throws GiftCardPaymentFailedException
     * @throws StripeTransferFailedException
     * @throws UnverifiedEmailException
     * @throws ServerErrorException
     * @throws StopEventException
     * @throws ApiErrorException
     * @throws GraphQLException
     */
    public function processIOSSubscriptionRenewals(ServerRequestInterface $request): JsonResponse
    {
        $this->manager->renewIOSSubscription($request->getParsedBody()['signedPayload']);
        return new JsonResponse([]);
    }
}
