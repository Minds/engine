<?php

namespace Spec\Minds\Core\Email\Confirmation;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Email\Confirmation\TokenCache;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;

class TokenCacheSpec extends ObjectBehavior
{
    /** @var PsrWrapper */
    protected $cache;

    public function let(
        PsrWrapper $cache
    ) {
        $this->cache = $cache;
        $this->beConstructedWith($cache);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TokenCache::class);
    }

    public function it_should_get_a_token() {
        $user = new User();
        $user->guid = '123';
        $this->setUser($user);
        $this->cache->get('email-confirmation:123')->shouldBeCalled();
        $this->get();
    }

    public function it_should_throw_on_get_if_no_user_set() {
        $this->shouldThrow(ServerErrorException::class)->duringGet();
    }


    public function it_should_set_a_token() {
        $user = new User();
        $user->guid = '123';
        $this->setUser($user);

        $this->cache->set(
            'email-confirmation:123',
            'ey123',
            86400
        )->shouldBeCalled();

        $this->set('ey123');
    }

    public function it_should_throw_on_set_if_no_user_set() {
        $this->shouldThrow(ServerErrorException::class)->duringSet('123');
    }

    public function it_should_get_and_set_user(
        User $user
    ) {
        $this->setUser($user);
        $this->getUser()->shouldReturn($user);
    }
}
