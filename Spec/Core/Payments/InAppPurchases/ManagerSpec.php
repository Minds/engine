<?php

namespace Spec\Minds\Core\Payments\InAppPurchases;

use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Manager;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_acknowledge_subscription(
        InAppPurchasesClientFactory $iapFactoryMock,
        GoogleInAppPurchasesClient $googleClientMock,
        User $userMock
    ) {
        $this->beConstructedWith($iapFactoryMock);

        $iapModel = new InAppPurchase(GoogleInAppPurchasesClient::class, "plus.monthly.001", "purchase-token", $userMock->getWrappedObject(), time() * 1000);
    
        $iapFactoryMock->createClient(GoogleInAppPurchasesClient::class)
            ->shouldBeCalled()
            ->willReturn($googleClientMock);

        $googleClientMock->acknowledgeSubscription($iapModel)
            ->shouldBeCalled()
            ->willReturn(true);

        $userMock->setPlusMethod('iap_google')
            ->shouldBeCalled();
        $userMock->setPlusExpires(time())
            ->shouldBeCalled();
        $userMock->save()->shouldBeCalled();

        $this->acknowledgeSubscription($iapModel)
            ->shouldBe(true);
    }
}
