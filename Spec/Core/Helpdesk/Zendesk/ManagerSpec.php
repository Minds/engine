<?php

namespace Spec\Minds\Core\Helpdesk\Zendesk;

use Minds\Core\Config\Config;
use Minds\Core\Helpdesk\Zendesk\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    private $config;

    public function let(
        Config $config
    ) {
        $this->config = $config;
        $this->config->get('zendesk')->shouldBeCalled()->willReturn(['private_key' => '123']);

        $this->beConstructedWith($config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_the_jwt_string(User $user)
    {
        $user->getUsername()->shouldBeCalled()->willReturn('test_user');
        $user->getEmail()->shouldBeCalled()->willReturn('email');
        $user->getGuid()->shouldBeCalled()->willReturn(123);

        /** {"typ":"JWT","alg":"HS256"} */
        $this->getJwt($user)->shouldContain('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9');
    }
}
