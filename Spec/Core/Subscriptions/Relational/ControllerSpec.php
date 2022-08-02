<?php

namespace Spec\Minds\Core\Subscriptions\Relational;

use Minds\Core\Subscriptions\Relational\Controller;
use Minds\Core\Subscriptions\Relational\Repository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    private $repositoryMock;

    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repositoryMock = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_return_subscriptions_of_subscriptions(
        ServerRequest $request,
        User $user1Mock
    ) {
        $loggedInUserMock = new User();
        $loggedInUserMock->guid = '123';

        $request->getAttribute('_user')
            ->willReturn($loggedInUserMock);

        $request->getQueryParams()
            ->willReturn([
                'limit' => 3,
            ]);

        //

        $this->repositoryMock->getSubscriptionsOfSubscriptions('123', 3, 0)
            ->willYield([
                $user1Mock->getWrappedObject(),
            ]);

        $user1Mock->export()
            ->willReturn([]);
    
        $jsonResponse = $this->getSubscriptionsOfSubscriptions($request);
        $jsonResponse->getBody()->getContents()->shouldBe(json_encode([
            'users' => [
                [],
            ]
        ]));
    }

    public function it_should_return_users_who_i_am_subscribed_to_that_also_subscribe_to_x_user(
        ServerRequest $request,
        User $user1Mock
    ) {
        $loggedInUserMock = new User();
        $loggedInUserMock->guid = '123';

        $request->getAttribute('_user')
            ->willReturn($loggedInUserMock);

        $request->getQueryParams()
            ->willReturn([
                'guid' => '456'
            ]);

        //

        $this->repositoryMock->getSubscriptionsThatSubscribeToCount('123', '456')
            ->willReturn(10);

        $this->repositoryMock->getSubscriptionsThatSubscribeTo('123', '456', 3, 0)
            ->willYield([
                $user1Mock->getWrappedObject(),
            ]);

        $user1Mock->export()
            ->willReturn([]);
    
        $jsonResponse = $this->getSubscriptionsThatSubscribeTo($request);
        $jsonResponse->getBody()->getContents()->shouldBe(json_encode([
            'count' => 10,
            'users' => [
                [],
            ]
        ]));
    }
}
