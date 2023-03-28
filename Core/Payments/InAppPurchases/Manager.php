<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Entities\User;
use NotImplementedException;

class Manager
{
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
    public function acknowledgePurchase(InAppPurchase $inAppPurchase): bool
    {
        $inAppPurchaseClient = $this->inAppPurchasesClientFactory->createClient($inAppPurchase->source);

        if (!$inAppPurchaseClient->acknowledgePurchase($inAppPurchase)) {
        }

        // TODO: potentially store user, subscriptionId and purchaseToken in a Vitess table for validation
        //       if same token can be claimed on different Minds accounts
        return true;
    }
}
