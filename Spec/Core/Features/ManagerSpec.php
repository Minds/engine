<?php

namespace Spec\Minds\Core\Features;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Minds\Common\Cookie;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var Cookie */
    protected $cookie;

    public function let(Config $config, Cookie $cookie)
    {
        $this->beConstructedWith($config);
        $this->config = $config;
        $this->cookie = $cookie;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_check_if_a_feature_exists_unsuccessfully_and_assume_its_inactive()
    {
        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => false]);

        $this->has('boost')->shouldReturn(false);
    }

    public function it_should_check_if_a_feature_exists_and_return_its_deactivated()
    {
        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => false]);

        $this->has('wire')->shouldReturn(false);
    }

    public function it_should_check_if_a_user_is_active_for_an_admin_and_return_true(User $user)
    {
        $user->isAdmin()
            ->shouldBeCalled()
            ->willReturn(true);

        //remove ip whitelist check
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.56.0.1';

        $this->setUser($user);

        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => 'admin']);

        $this->has('wire')->shouldReturn(true);
    }

    public function it_should_check_if_a_user_is_active_for_an_admin_and_return_false(User $user)
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

    public function it_should_export_all_features()
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

    public function it_should_return_has_staging_features_cookie_when_set()
    {
        $_COOKIE['staging-features'] = 'eyJ0ZXN0Ijp0cnVlfQ==';
        $this->has('test')->shouldReturn(true);
    }


    public function it_should_when_exporting_include_overriden_staging_features()
    {
        $this->config->get('features')
            ->shouldBeCalled()
            ->willReturn(['plus' => true, 'wire' => 'admin']);

        $_COOKIE['staging-features'] = 'eyJ0ZXN0Ijp0cnVlfQ==';
        
        $this->export()->shouldReturn([
            'test' => true,
            'plus' => true,
            'wire' => 'admin'
        ]);
    }
}
