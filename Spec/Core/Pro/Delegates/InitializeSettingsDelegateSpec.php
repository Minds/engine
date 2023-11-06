<?php

namespace Spec\Minds\Core\Pro\Delegates;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Pro\Delegates\InitializeSettingsDelegate;
use Minds\Core\Pro\Delegates\SetupRoutingDelegate;
use Minds\Core\Pro\Repository;
use Minds\Core\Pro\Settings;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class InitializeSettingsDelegateSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;


    public function let(
        Repository $repository,
    ) {
        $this->repository = $repository;

        $this->beConstructedWith($repository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(InitializeSettingsDelegate::class);
    }

    public function it_should_initialize_settings_on_enable(
        User $user,
        Response $getListResponse,
        Settings $settings
    ) {
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn('PHPSpec');

        $this->repository->getList([
            'user_guid' => 1000
        ])
            ->shouldBeCalled()
            ->willReturn($getListResponse);

        $getListResponse->first()
            ->shouldBeCalled()
            ->willReturn($settings);

        $settings->getTitle()
            ->shouldBeCalled()
            ->willReturn('');

        $settings->setTitle('PHPSpec')
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->repository->add($settings)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->shouldNotThrow(Exception::class)
            ->duringOnEnable($user);
    }
}
