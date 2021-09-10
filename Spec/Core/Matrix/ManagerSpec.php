<?php
namespace Spec\Minds\Core\Matrix;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Core\Notifications;
use Minds\Core\Matrix\MatrixConfig;
use Minds\Core\Matrix\Client;
use Minds\Core\Log\Logger;
use Minds\Core\Matrix\MatrixRoom;

class ManagerSpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;

    /** @var MatrixConfig */
    protected $matrixConfig;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Notifications\Manager */
    protected $notificationsManager;

    /** @var Logger */
    protected $logger;

    public function let(Client $client, MatrixConfig $matrixConfig, EntitiesBuilder $entitiesBuilder, Notifications\Manager $notificationsManager, Logger $logger)
    {
        $this->beConstructedWith($client, $matrixConfig, $entitiesBuilder, $notificationsManager, $logger);
        $this->client = $client;
        $this->matrixConfig = $matrixConfig;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->notificationsManager = $notificationsManager;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Matrix\Manager');
    }

    public function it_should_send_chat_invite_notification(User $sender, User $receiver, MatrixRoom $room)
    {
        $sender->getGuid()
            ->willReturn('123');

        $receiver->getGuid()
            ->willReturn('456');

        $this->notificationsManager->add(Argument::that(function ($notification) {
            return $notification->getToGuid() === '456';
        }))
            ->willReturn(true);

        $this->sendChatInviteNotification($sender, $receiver, $room);
    }
}
