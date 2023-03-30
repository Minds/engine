<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Clients;

use Google;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class GoogleInAppPurchasesClient implements InAppPurchaseClientInterface
{
    private const PACKAGE_NAME = "com.minds.mobile";
    private const API_BASE_URI = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/" . self::PACKAGE_NAME . "/purchases/subscriptions/";

    public function __construct(
        private ?MindsConfig $mindsConfig = null,
        private ?Google\Client $googleClient = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->googleClient ??= new \Google\Client();

        $serviceAccountPath = $this->mindsConfig->get('google')['iap']['service_account']['key_path'];
        $this->googleClient->setApplicationName('minds-engine');
        $this->googleClient->setScopes(['https://www.googleapis.com/auth/androidpublisher']);
        $this->googleClient->setAuthConfig($serviceAccountPath);
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @param User $user
     * @return bool
     */
    public function acknowledgeSubscription(InAppPurchase $inAppPurchase): bool
    {
        try {
            $androidPublisherService = new Google\Service\AndroidPublisher($this->googleClient);

            // Fetch the subscription
            $subscriptionPurchase = $androidPublisherService->purchases_subscriptions->get(
                self::PACKAGE_NAME,
                $inAppPurchase->subscriptionId,
                $inAppPurchase->purchaseToken,
            );

            if (!$subscriptionPurchase) {
                throw new UserErrorException("Subscription not found");
            }

            /**
             * Confirm the subscription hasn't expired
             */
            if ($subscriptionPurchase->expiryTimeMillis < time() * 1000) {
                throw new UserErrorException("Subscription has expired");
            }

            /**
             * Confirm the user is the one we expect
             */
            if ($subscriptionPurchase->getObfuscatedExternalAccountId() !== (string) $inAppPurchase->user->getGuid()) {
                throw new ForbiddenException("The subscription has already been consumed by another user.");
            }

            $androidPublisherService->purchases_subscriptions->acknowledge(
                self::PACKAGE_NAME,
                $inAppPurchase->subscriptionId,
                $inAppPurchase->purchaseToken,
                new Google\Service\AndroidPublisher\SubscriptionPurchasesAcknowledgeRequest([]),
            );
        } catch (Google\Service\Exception $e) {
            // We should process these errors?
            throw new UserErrorException($e->getMessage());
        }

        return true;
    }
}
