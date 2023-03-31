<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Google;

use Google;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchaseClientInterface;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class GoogleInAppPurchasesClient implements InAppPurchaseClientInterface
{
    private const PACKAGE_NAME = "com.minds.mobile";

    public function __construct(
        private ?MindsConfig $mindsConfig = null,
        private ?Google\Client $googleClient = null,
        private ?Google\Service\AndroidPublisher $androidPublisherService = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->googleClient ??= new \Google\Client();

        $serviceAccountPath = $this->mindsConfig->get('google')['iap']['service_account']['key_path'];
        $this->googleClient->setApplicationName('minds-engine');
        $this->googleClient->setScopes(['https://www.googleapis.com/auth/androidpublisher']);
        $this->googleClient->setAuthConfig($serviceAccountPath);

        $this->androidPublisherService = new Google\Service\AndroidPublisher($this->googleClient);
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
            $subscriptionPurchase = $this->getSubscription($inAppPurchase);

            if (!$subscriptionPurchase) {
                throw new UserErrorException("Subscription not found");
            }

            // Update InAppPurchase with the expiry time (as the manager wants to know this information later)
            $inAppPurchase->setExpiresMillis((int) $subscriptionPurchase->expiryTimeMillis);

            // Confirm the subscription hasn't expired
            if ($subscriptionPurchase->expiryTimeMillis < time() * 1000) {
                throw new UserErrorException("Subscription has expired");
            }

            // Confirm the user is the one we expect
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

    /**
     * @param InAppPurchase $inAppPurchase
     * @return Google\Service\AndroidPublisher\SubscriptionPurchase
     */
    public function getSubscription(InAppPurchase $inAppPurchase): Google\Service\AndroidPublisher\SubscriptionPurchase
    {
        return $this->androidPublisherService->purchases_subscriptions->get(
            self::PACKAGE_NAME,
            $inAppPurchase->subscriptionId,
            $inAppPurchase->purchaseToken,
        );
    }
}
