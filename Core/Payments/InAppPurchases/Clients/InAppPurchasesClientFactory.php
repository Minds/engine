<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Clients;

use NotImplementedException;

/**
 * Factory responsible to create an instance of the InAppPurchaseClientInterface
 */
class InAppPurchasesClientFactory
{
    /**
     * @param string $clientClassName
     * @return InAppPurchaseClientInterface
     * @throws NotImplementedException
     */
    public function createClient(string $clientClassName): InAppPurchaseClientInterface
    {
        return match ($clientClassName) {
            GoogleInAppPurchasesClient::class => new GoogleInAppPurchasesClient(),
            AppleInAppPurchasesClient::class => new AppleInAppPurchasesClient(),
            default => throw new NotImplementedException()
        };
    }
}
