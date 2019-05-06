<?php

namespace Spec\Minds\Core\Features;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    function let(Config $config)
    {
        Di::_()->bind('Config', function ($di) use ($config) {
            return $config->getWrappedObject();
        });

        $this->config = $config;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    function it_should_check_if_a_feature_exists_unsuccessfully_and_assume_its_active()
    {
        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => false]);

        $this->has('boost')->shouldReturn(true);
    }

    function it_should_check_if_a_feature_exists_and_return_its_deactivated()
    {
        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => false]);

        $this->has('wire')->shouldReturn(false);
    }

    function it_should_check_if_a_user_is_active_for_an_admin_and_return_true(User $user)
    {
        $user = new User();
        $user->guid = '1234';
        $user->admin = true;

        //remove ip whitelist check
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.56.0.1';

        $this->setUser($user);

        $this->config->get('development_mode')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => 'admin']);

        $this->config->get('last_tos_update')
            ->shouldBeCalled()
            ->willReturn(123456);

        $this->config->get('admin_ip_whitelist')
            ->shouldBeCalled()
            ->willReturn([ '10.56.0.1' ]);

        $this->has('wire')->shouldReturn(true);
    }

    function it_should_check_if_a_user_is_active_for_an_admin_and_return_true_development_node(User $user)
    {
        $user = new User();
        $user->guid = '1234';
        $user->admin = true;

        //remove ip whitelist check
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.56.0.1';

        $this->setUser($user);

        $this->config->get('development_mode')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => 'admin']);

        $this->config->get('last_tos_update')
            ->shouldBeCalled()
            ->willReturn(123456);

        $this->config->get('admin_ip_whitelist')
            ->shouldNotBeCalled();

        $this->has('wire')->shouldReturn(true);
    }

    function it_should_check_if_a_user_is_active_for_an_admin_and_return_false(User $user)
    {
        $user->guid = '1234';
        $user->admin = false;

        $this->setUser($user);

        //$this->config->get('last_tos_update')
        //    ->shouldBeCalled()
        //    ->willReturn(123456);

        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => 'admin']);

        $this->has('wire')->shouldReturn(false);
    }

    function it_should_export_all_features()
    {
        $features = [
            'plus' => true,
            'wire' => 'admin',
            'boost' => false,
        ];

        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn($features);

        $this->export()->shouldReturn($features);
    }
}
