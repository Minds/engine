<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Google;

use Google;
use Google\Cloud\PubSub\PubSubClient;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Payments\InAppPurchases\Manager;
use Minds\Entities\User;

class GoogleInAppPurchasesPubSub
{
    public function __construct(
        private ?Config $mindsConfig = null,
        private ?PubSubClient $pubSubClient = null,
        private ?GoogleInAppPurchasesClient $googleInAppPurchasesClient = null,
        private ?Manager $manager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->pubSubClient ??= new Google\Cloud\PubSub\PubSubClient([
            'keyFilePath' => $this->mindsConfig->get('google')['iap']['service_account']['key_path'],
        ]);
        $this->googleInAppPurchasesClient ??= Di::_()->get(InAppPurchasesClientFactory::class)->createClient(GoogleInAppPurchasesClient::class);
        $this->manager ??= Di::_()->get(Manager::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    /**
     * Will receive and process PubSub Messages
     */
    public function receivePubSubMessages(): void
    {
        $pubSubSubscription = $this->pubSubClient->subscription($this->mindsConfig->get('google')['iap']['pubsub']['subscription']);

        $messages = $pubSubSubscription->pull([ 'returnImmediately' => true ]);
        foreach ($messages as $message) {
            $rawData = $message->data();
            $data = json_decode($rawData, true);

            if (!isset($data['subscriptionNotification'])) {
                $pubSubSubscription->acknowledge($message);
                continue; // Invalid message type, we dont care
            }

            // Construct the InAppPurchase model
            $inAppPurchase = new InAppPurchase(GoogleInAppPurchasesClient::class, $data['subscriptionNotification']['subscriptionId'], $data['subscriptionNotification']['purchaseToken']);

            // Fetch the subscription so we know who the purchase user is
            $subscription = $this->googleInAppPurchasesClient->getSubscription($inAppPurchase);
            $userGuid = $subscription->getObfuscatedExternalAccountId();

            // Fetch the User Entity
            $user = $this->entitiesBuilder->single($userGuid);

            if (!$user instanceof User) {
                $pubSubSubscription->acknowledge($message);
                continue; // User not found, can't proceed
            }

            // Add the User to the InAppPurchase model
            $inAppPurchase->setUser($user);

            // Submit the InAppPurchase model the the manager and process payment
            $success = $this->manager->acknowledgeSubscription($inAppPurchase);

            // Ack the pub sub message so we dont reprocess
            $pubSubSubscription->acknowledge($message);
        }
    }
}
