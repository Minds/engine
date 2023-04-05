<?php

namespace Spec\Minds\Core\Payments\InAppPurchases\Google;

use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
use Minds\Core\Payments\InAppPurchases\Manager;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesPubSub;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class GoogleInAppPurchasesPubSubSpec extends ObjectBehavior
{
    /** @var Config */
    protected $configMock;

    /** @var PubSubClient */
    protected $pubSubClientMock;

    /** @var GoogleInAppPurchasesClient */
    protected $googleInAppPurchasesClientMock;

    /** @var Manager */
    protected $managerMock;

    /** @var EntitiesBuilder */
    protected $entitiesBuilderMock;

    public function let(
        Config $configMock,
        PubSubClient $pubSubClientMock,
        GoogleInAppPurchasesClient $googleInAppPurchasesClientMock,
        Manager $managerMock,
        EntitiesBuilder $entitiesBuilderMock,
    ) {
        $this->beConstructedWith($configMock, $pubSubClientMock, $googleInAppPurchasesClientMock, $managerMock, $entitiesBuilderMock);
        $this->configMock = $configMock;
        $this->pubSubClientMock = $pubSubClientMock;
        $this->googleInAppPurchasesClientMock = $googleInAppPurchasesClientMock;
        $this->managerMock = $managerMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(GoogleInAppPurchasesPubSub::class);
    }

    public function it_should_process_a_pubsub_message(
        Subscription $pubSubSubscriptionMock,
        Message $pubSubMessageMock,
        SubscriptionPurchase $subscriptionPurchaseMock,
    ) {
        $this->pubSubClientMock->subscription('subscriptionName')
            ->willReturn($pubSubSubscriptionMock);

        $this->configMock->get('google')->willReturn([
            'iap' => [
                'pubsub' => [
                    'subscription' => 'subscriptionName',
                ]
            ]
        ]);

        $pubSubSubscriptionMock->pull(Argument::any())->willReturn([ $pubSubMessageMock ]);

        $pubSubMessageMock->data()
            ->willReturn(json_encode([
                'subscriptionNotification' => [
                    'subscriptionId' => 'subId',
                    'purchaseToken' => 'token...'
                ]
            ]));

        $this->googleInAppPurchasesClientMock->getSubscription(Argument::any())
            ->willReturn($subscriptionPurchaseMock);

        $subscriptionPurchaseMock->getObfuscatedExternalAccountId()
            ->willReturn('123');

        $user = new User();

        $this->entitiesBuilderMock->single('123')
            ->willReturn($user);

        $this->managerMock->acknowledgeSubscription(Argument::that(function (InAppPurchase $iapModel) {
            return true;
        }))
            ->shouldBeCalled();
        
        $pubSubSubscriptionMock->acknowledge(Argument::any())
            ->shouldBeCalled();

        $this->receivePubSubMessages();
    }
}
