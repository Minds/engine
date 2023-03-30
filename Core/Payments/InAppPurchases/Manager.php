<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\InAppPurchases\Clients\AppleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Clients\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Entities\User;
use NotImplementedException;

class Manager
{
    const GOOGLE = 'google';
    const APPLE = 'apple';

    private User $user;

    public function __construct(
        private ?InAppPurchasesClientFactory $inAppPurchasesClientFactory = null,
        private ?Logger $logger = null
    ) {
        $this->inAppPurchasesClientFactory ??= Di::_()->get(InAppPurchasesClientFactory::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return bool
     * @throws NotImplementedException
     */
    public function acknowledgeSubscription(InAppPurchase $inAppPurchase): bool
    {
        $inAppPurchaseClient = $this->inAppPurchasesClientFactory->createClient($inAppPurchase->source);

        if (!$inAppPurchaseClient->acknowledgeSubscription($inAppPurchase)) {
            return false;
        }

        $method = match ($inAppPurchase->source) {
            GoogleInAppPurchasesClient::class => 'iap_google',
            AppleInAppPurchasesClient::class => 'iap_apple',
        };

        /**
         * Not ideal, but short term solution
         */
        switch ($inAppPurchase->subscriptionId) {
            case "plus.monthly.001":
                $inAppPurchase->user->setPlusMethod($method);
                $inAppPurchase->user->setPlusExpires(strtotime("32 days"));
                break;
            case "plus.yearly.001":
                $inAppPurchase->user->setPlusMethod($method);
                $inAppPurchase->user->setPlusExpires(strtotime("366 days"));
                break;
            case "pro.monthly.001":
                $inAppPurchase->user->setProMethod($method);
                $inAppPurchase->user->setProExpires(strtotime("32 days"));
                break;
        }

        $inAppPurchase->user->save();

        return true;
    }
}
