<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Common\SystemUser;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardPaymentFailedException;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\InAppPurchases\Apple\AppleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Apple\Enums\ApplePurchaseStatusEnum;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Payments\InAppPurchases\Models\ProductPurchase;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Manager
{
    const GOOGLE = 'google';
    const APPLE = 'apple';

    public function __construct(
        private readonly GiftCardsManager $giftCardsManager,
        private readonly Config $config,
        private ?InAppPurchasesClientFactory $inAppPurchasesClientFactory = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Logger $logger = null,
        private ?Save $save = null,
    ) {
        $this->inAppPurchasesClientFactory ??= Di::_()->get(InAppPurchasesClientFactory::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->logger ??= Di::_()->get('Logger');
        $this->save ??= new Save();
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return bool
     * @throws NotImplementedException
     * @throws GiftCardPaymentFailedException
     * @throws StripeTransferFailedException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     * @throws GraphQLException
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

        $amount = null;

        // TODO: purchaseToken is not used and we should store it for reconciliation

        /**
         * Not ideal, but short term solution
         */
        $result = match ($inAppPurchase->subscriptionId) {
            "plus.yearly.001",
            "plus.monthly.001",
            "plus.yearly.01",
            "plus.monthly.01"=> function () use ($inAppPurchase, $method, &$amount): string {
                $amount = $this->config->get('upgrades')['plus']['monthly']['usd'];
                $inAppPurchase->setExpiresMillis(strtotime("+1 month") * 1000);
                if ($inAppPurchase->subscriptionId === "plus.yearly.001") {
                    $amount = $this->config->get('upgrades')['plus']['yearly']['usd'];
                    $inAppPurchase->setExpiresMillis(strtotime("+1 year") * 1000);
                }

                $inAppPurchase->user->setPlusMethod($method);
                $inAppPurchase->user->setPlusExpires($inAppPurchase->expiresMillis / 1000);
                return "plus";
            },
            "pro.monthly.01",
            "pro.monthly.001" => function () use ($inAppPurchase, $method, &$amount): string {
                $amount = $this->config->get('upgrades')['pro']['monthly']['usd'];
                $inAppPurchase->setExpiresMillis(strtotime("+1 month") * 1000);

                $inAppPurchase->user->setProMethod($method);
                $inAppPurchase->user->setProExpires($inAppPurchase->expiresMillis / 1000);
                return "pro";
            },
            default => null
        };

        if (!$result) {
            throw new UserErrorException("Invalid subscriptionId");
        }

        $subscriptionType = $result();

        $this->save
            ->setEntity($inAppPurchase->user)
            ->withMutatedAttributes([
                'pro_method',
                'pro_expires',
                'plus_method',
                'plus_expires',
            ])
            ->save();

        $sender = match ($inAppPurchase->subscriptionId) {
            "plus.yearly.001",
            "plus.yearly.01",
            "plus.monthly.01",
            "plus.monthly.001" => $this->entitiesBuilder->single($this->config->get('plus')['handler']),
            "pro.yearly.001",
            "pro.monthly.01",
            "pro.monthly.001" => $this->entitiesBuilder->single($this->config->get('pro')['handler']),
        };

        $this->giftCardsManager->issueMindsPlusAndProGiftCards(
            sender: $sender ?? new SystemUser(),
            recipient: $inAppPurchase->user,
            amount: $amount,
            expiryTimestamp: $subscriptionType === "plus" ? $inAppPurchase->user->getPlusExpires() : $inAppPurchase->user->getProExpires()
        );

        return true;
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return ProductPurchase
     * @throws GuzzleException
     * @throws NotImplementedException
     */
    public function getProductPurchaseDetails(InAppPurchase $inAppPurchase): ProductPurchase
    {
        return match ($inAppPurchase->source) {
            GoogleInAppPurchasesClient::class => $this->fetchAndroidProductPurchase($inAppPurchase),
            AppleInAppPurchasesClient::class => $this->fetchAppleProductPurchase($inAppPurchase),
            default => throw new NotImplementedException("getProductPurchaseDetails"),
        };
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return mixed
     * @throws NotImplementedException
     * @throws GuzzleException
     */
    private function fetchAppleProductPurchase(InAppPurchase $inAppPurchase): ProductPurchase
    {
        /**
         * @var AppleInAppPurchasesClient $inAppPurchaseClient
         */
        $inAppPurchaseClient = $this->inAppPurchasesClientFactory->createClient(AppleInAppPurchasesClient::class);
        
        $appleTransaction = $inAppPurchaseClient->getTransaction($inAppPurchase->transactionId);

        return new ProductPurchase(
            productId: $inAppPurchase->productId,
            transactionId: $inAppPurchase->transactionId,
            acknowledged: $appleTransaction->purchaseState === ApplePurchaseStatusEnum::purchased
        );
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return ProductPurchase
     * @throws NotImplementedException
     */
    private function fetchAndroidProductPurchase(InAppPurchase $inAppPurchase): ProductPurchase
    {
        /** @var GoogleInAppPurchasesClient $inAppPurchaseClient
         *
         */
        $inAppPurchaseClient = $this->inAppPurchasesClientFactory->createClient(GoogleInAppPurchasesClient::class);

        $androidProductPurchase = $inAppPurchaseClient->getInAppPurchaseProductPurchase($inAppPurchase);

        return new ProductPurchase(
            productId: $inAppPurchase->productId,
            transactionId: $androidProductPurchase->getOrderId() . ":" . ($androidProductPurchase->getPurchaseToken() ?? $inAppPurchase->purchaseToken),
            acknowledged: (bool) $androidProductPurchase->getAcknowledgementState()
        );
    }
}
