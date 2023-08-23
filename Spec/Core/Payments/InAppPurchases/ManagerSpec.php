<?php

namespace Spec\Minds\Core\Payments\InAppPurchases;

use Minds\Core\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Manager;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $giftCardsManagerMock;
    private Collaborator $configMock;
    private Collaborator $iapFactoryMock;
    private Collaborator $entitiesBuilderMock;

    public function let(
        GiftCardsManager $giftCardsManager,
        Config $config,
        InAppPurchasesClientFactory $iapFactory,
        EntitiesBuilder $entitiesBuilderMock
    ): void {
        $this->giftCardsManagerMock = $giftCardsManager;
        $this->configMock = $config;
        $this->iapFactoryMock = $iapFactory;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->beConstructedWith(
            $this->giftCardsManagerMock,
            $this->configMock,
            $this->iapFactoryMock,
            $this->entitiesBuilderMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_acknowledge_subscription(
        GoogleInAppPurchasesClient $googleClientMock,
        User $userMock
    ) {
        $userMock->setPlusMethod('iap_google')
            ->shouldBeCalled();
        $userMock->setPlusExpires(Argument::type('int'))
            ->shouldBeCalled();
        $userMock->save()->shouldBeCalled();
        $userMock->getPlusExpires()
            ->shouldBeCalledOnce()
            ->willReturn(time());

        $iapModel = new InAppPurchase(
            GoogleInAppPurchasesClient::class,
            "plus.monthly.001",
            "purchase-token",
            $userMock->getWrappedObject(),
            time() * 1000
        );

        $this->configMock->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' => [
                    'monthly' => [
                        'usd' => 5.99
                    ]
                ]
            ]);

        $this->configMock->get('plus')
            ->shouldBeCalledOnce()
            ->willReturn([
                'handler' => '123'
            ]);

        $this->entitiesBuilderMock->single(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($userMock);

        $this->giftCardsManagerMock->issueMindsPlusAndProGiftCards($userMock, $userMock, Argument::type('float'), Argument::type('int'))
            ->shouldBeCalledOnce();

        $googleClientMock->acknowledgeSubscription($iapModel)
            ->shouldBeCalled()
            ->willReturn(true);
    
        $this->iapFactoryMock->createClient(GoogleInAppPurchasesClient::class)
            ->shouldBeCalled()
            ->willReturn($googleClientMock);

        $this->acknowledgeSubscription($iapModel)
            ->shouldBe(true);
    }
}
