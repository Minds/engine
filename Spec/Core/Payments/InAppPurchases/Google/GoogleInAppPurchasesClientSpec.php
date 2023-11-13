<?php

namespace Spec\Minds\Core\Payments\InAppPurchases\Google;

use Google\Client as GoogleClient;
use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
use Minds\Core\Config\Config;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class GoogleInAppPurchasesClientSpec extends ObjectBehavior
{
    /** @var AndroidPublisher */
    protected $androidPublisherMock;

    /** @var PurchasesSubscriptions */
    protected $purchasesSubscriptionsMock;

    public function let(
        Config $configMock,
        GoogleClient $googleClientMock,
        AndroidPublisher $androidPublisherMock,
        PurchasesSubscriptions $purchasesSubscriptionsMock
    ) {
        $configMock->get('google')->willReturn([
            'iap' => [
                'service_account' => [
                    'key_path' => './key.json'
                ]
            ]
        ]);
        $this->beConstructedWith($configMock, $googleClientMock, $androidPublisherMock);
        $androidPublisherMock->purchases_subscriptions = $purchasesSubscriptionsMock;
        $this->androidPublisherMock = $androidPublisherMock;
        $this->purchasesSubscriptionsMock = $purchasesSubscriptionsMock;

        // $this->googleClientMock->setApplicationName(Argument::any())
        //     ->shouldBeCalled();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(GoogleInAppPurchasesClient::class);
    }

    public function it_should_not_process_expired_subscriptions(): void
    {
        $iapModel = new InAppPurchase(
            source: GoogleInAppPurchasesClient::class,
            subscriptionId: 'plus.monthly.001',
            purchaseToken: 'hneelmickjdkkonpiekipohg.AO-J1OxcIWRz9T_Btw6N-ZSzyJqVx6_G_6fuxXQO8AE40VMY6pPL4bWx3tDajcqiUXkn1rRZ0Lek85ohoXVhQVssDy_Bfpl_RA'
        );

        $subscriptionPurchase = new SubscriptionPurchase();
        $subscriptionPurchase->expiryTimeMillis = (string) time() - 100;

        $this->purchasesSubscriptionsMock
            ->get('com.minds.mobile', 'plus.monthly.001', 'hneelmickjdkkonpiekipohg.AO-J1OxcIWRz9T_Btw6N-ZSzyJqVx6_G_6fuxXQO8AE40VMY6pPL4bWx3tDajcqiUXkn1rRZ0Lek85ohoXVhQVssDy_Bfpl_RA')
            ->willReturn($subscriptionPurchase);

        $this->androidPublisherMock->purchases_subscriptions = $this->purchasesSubscriptionsMock;

        $this->shouldThrow(UserErrorException::class)->duringAcknowledgeSubscription($iapModel);
    }

    public function it_should_not_process_if_not_owner()
    {
        $user = new User();
        $user->guid = '122';

        $iapModel = new InAppPurchase(
            source: GoogleInAppPurchasesClient::class,
            subscriptionId: 'plus.monthly.001',
            purchaseToken: 'hneelmickjdkkonpiekipohg.AO-J1OxcIWRz9T_Btw6N-ZSzyJqVx6_G_6fuxXQO8AE40VMY6pPL4bWx3tDajcqiUXkn1rRZ0Lek85ohoXVhQVssDy_Bfpl_RA',
            user: $user
        );
        $this->androidPublisherMock->purchases_subscriptions = $this->purchasesSubscriptionsMock;

        $subscriptionPurchase = new SubscriptionPurchase();
        $subscriptionPurchase->expiryTimeMillis = (string) (time() + 100) * 1000;
        $subscriptionPurchase->obfuscatedExternalAccountId = '123';

        $this->purchasesSubscriptionsMock
            ->get('com.minds.mobile', 'plus.monthly.001', 'hneelmickjdkkonpiekipohg.AO-J1OxcIWRz9T_Btw6N-ZSzyJqVx6_G_6fuxXQO8AE40VMY6pPL4bWx3tDajcqiUXkn1rRZ0Lek85ohoXVhQVssDy_Bfpl_RA')
            ->willReturn($subscriptionPurchase);

        $this->shouldThrow(ForbiddenException::class)->duringAcknowledgeSubscription($iapModel);
    }

    public function it_should_acknowledge()
    {
        $user = new User();
        $user->guid = '123';

        $iapModel = new InAppPurchase(
            source: GoogleInAppPurchasesClient::class,
            subscriptionId: 'plus.monthly.001',
            purchaseToken: 'hneelmickjdkkonpiekipohg.AO-J1OxcIWRz9T_Btw6N-ZSzyJqVx6_G_6fuxXQO8AE40VMY6pPL4bWx3tDajcqiUXkn1rRZ0Lek85ohoXVhQVssDy_Bfpl_RA',
            user: $user
        );
        $this->androidPublisherMock->purchases_subscriptions = $this->purchasesSubscriptionsMock;

        $subscriptionPurchase = new SubscriptionPurchase();
        $subscriptionPurchase->expiryTimeMillis = (string) (time() + 100) * 1000;
        $subscriptionPurchase->obfuscatedExternalAccountId = '123';

        $this->purchasesSubscriptionsMock
            ->get('com.minds.mobile', 'plus.monthly.001', 'hneelmickjdkkonpiekipohg.AO-J1OxcIWRz9T_Btw6N-ZSzyJqVx6_G_6fuxXQO8AE40VMY6pPL4bWx3tDajcqiUXkn1rRZ0Lek85ohoXVhQVssDy_Bfpl_RA')
            ->willReturn($subscriptionPurchase);

        $this->purchasesSubscriptionsMock
            ->acknowledge('com.minds.mobile', 'plus.monthly.001', 'hneelmickjdkkonpiekipohg.AO-J1OxcIWRz9T_Btw6N-ZSzyJqVx6_G_6fuxXQO8AE40VMY6pPL4bWx3tDajcqiUXkn1rRZ0Lek85ohoXVhQVssDy_Bfpl_RA', Argument::any())
            ->shouldBeCalled();

        $this->acknowledgeSubscription($iapModel)
            ->shouldBe(true);
    }
}
