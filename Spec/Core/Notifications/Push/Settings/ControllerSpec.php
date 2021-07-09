<?php

namespace Spec\Minds\Core\Notifications\Push\Settings;

use Minds\Core\Notifications\Push\Settings\Manager;
use Minds\Core\Notifications\Push\Settings\Controller;
use Minds\Core\Notifications\Push\Settings\PushSetting;
use Minds\Core\Notifications\Push\Settings\SettingsListOpts;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_get_list(ServerRequest $request, User $user)
    {
        $request->getAttribute('_user')
            ->willReturn($user);

        $user->getGuid()
            ->willReturn('123');

        $this->manager->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === '123';
        }))
            ->willReturn([
                (new PushSetting)
                    ->setUserGuid('123')
                    ->setNotificationGroup('all')
                    ->setEnabled(true),
            ]);
        $jsonResponse = $this->getSettings($request);
        $json = $jsonResponse->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'settings' => [
                [
                    'notification_group' => 'all',
                    'enabled' => true,
                ]
            ]
        ]));
    }

    public function it_shoud_toggle_setting(ServerRequest $request, User $user)
    {
        $request->getAttribute('_user')
            ->willReturn($user);

        $request->getAttribute('parameters')
            ->willReturn([
                'notificationGroup' => 'all'
            ]);

        $request->getParsedBody()
            ->willReturn([
                'enabled' => false,
            ]);

        $user->getGuid()
            ->willReturn('123');

        $this->manager->add(Argument::that(function ($pushSetting) {
            return $pushSetting->getUserGuid() === '123'
                    && $pushSetting->getNotificationGroup() === 'all'
                    && $pushSetting->getEnabled() === false;
        }))
            ->willReturn(true);

        $jsonResponse = $this->toggle($request);
        $json = $jsonResponse->getBody()->getContents();
    }
}
