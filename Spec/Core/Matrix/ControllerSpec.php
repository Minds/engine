<?php
namespace Spec\Minds\Core\Matrix;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Matrix\Controller;
use Minds\Core\Matrix\Manager;
use Minds\Core\Matrix\MatrixRoom;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(Manager $manager, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($manager, $entitiesBuilder);
        $this->manager = $manager;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Matrix\Controller');
    }

    public function it_should_send_chat_invite_notification_when_receiver_has_no_matrix_account(ServerRequest $request)
    {
        $user = new User();

        $receiver = new User();

        $room = new MatrixRoom;

        $request->getAttribute('_user')
            ->willReturn($user);

        $request->getAttribute('parameters')
                ->willReturn([
                    'receiverGuid' => '456',
                ]);

        $this->entitiesBuilder->single('456')->willReturn($receiver);

        $this->manager->createDirectRoom($user, $receiver)
            ->willReturn($room);

        $this->manager->getAccountByUser($receiver)
            ->willReturn(null);

        $this->manager->sendChatInviteNotification($user, $receiver, $room)
            ->shouldBeCalled();

        $response = $this->createDirectRoom($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'room' => $room->export()
        ]));
    }

    // public function it_should_get_total_unread(
    //     ServerRequest $request
    // ) {
    //     $user = new User();
    //     $user->guid = '123';

    //     $request->getAttribute('_user')
    //         ->willReturn($user);

    //     $roomA = new MatrixRoom();
    //     $roomA->setUnreadCount(7);

    //     $roomB = new MatrixRoom();
    //     $roomB->setUnreadCount(42);

    //     $joinedRooms = [$roomA, $roomB];

    //     $this->manager->getJoinedRooms($user)
    //         ->willReturn($joinedRooms);

    //     $response = $this->getTotalUnread($request);
    //     $json = $response->getBody()->getContents();

    //     $json->shouldBe(json_encode([
    //         'status' => 'success',
    //         'total_unread' => 49
    //     ]));
    // }
}
