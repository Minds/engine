<?php

namespace Spec\Minds\Core\Payments\InAppPurchases;

use Minds\Core\Payments\InAppPurchases\Controller;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Manager;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $managerMock;

    public function let(Manager $managerMock)
    {
        $this->beConstructedWith($managerMock);
        $this->managerMock = $managerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_reject_if_no_payload(ServerRequest $requestMock)
    {
        $this->shouldThrow(UserErrorException::class)->duringAcknowledgeSubscription($requestMock);
    }

    public function it_should_return_success(ServerRequest $requestMock)
    {
        $requestMock->getAttribute('_user')
            ->willReturn(new User());

        $requestMock->getParsedBody()
            ->willReturn([
                'service' => 'google',
                'subscriptionId' => 'plus.monthly.001',
                'purchaseToken' => 'example'
            ]);

        $this->managerMock->acknowledgeSubscription(Argument::that(function (InAppPurchase $iapModel) {
            return $iapModel->source === GoogleInAppPurchasesClient::class
                && $iapModel->subscriptionId === 'plus.monthly.001'
                && $iapModel->purchaseToken === 'example';
        }))
            ->shouldBeCalled();

        $jsonResponse = $this->acknowledgeSubscription($requestMock);
    }
}
