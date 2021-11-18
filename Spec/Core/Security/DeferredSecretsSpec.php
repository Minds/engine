<?php

namespace Spec\Minds\Core\Security;

use Minds\Core\Data\Redis\Client;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DeferredSecretsSpec extends ObjectBehavior
{
    private $redis;
    private $logger;

    public function let(Client $redis, Logger $logger)
    {
        $this->beConstructedWith($redis, $logger);
        $this->redis = $redis;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Security\DeferredSecrets');
    }

    public function it_should_generate_a_random_secret_and_store_it_in_cache(User $user)
    {
        $userGuid = '123';
        $user->getGuid()->shouldBeCalledTimes(3)->willReturn($userGuid);

        // should generate a 256 char long string
        $this->generate($user)->shouldMatch('/^.{256}$/i');

        // generating subsequent secrets should not generate the same string.
        $this->generate($user)->shouldNotEqual($this->generate($user));

        $this->redis->set("deferred-secret:$userGuid", Argument::any(), 300)
            ->shouldHaveBeenCalledTimes(3);
    }

    public function it_should_verify_a_valid_secret_that_matched_the_cached_secret(User $user)
    {
        $secret = '123';
        $cachedSecret = '123';
        $userGuid = '999999999';
        $user->getGuid()->shouldBeCalled()->willReturn($userGuid);

        $this->redis->get("deferred-secret:$userGuid")
            ->shouldBeCalled()
            ->willReturn($cachedSecret);

        $this->redis->delete("deferred-secret:$userGuid")
            ->shouldBeCalled();

        $this->verify($secret, $user)->shouldBe(true);
    }

    public function it_should_NOT_verify_a_valid_secret_that_DOES_NOT_match_the_cached_secret(User $user)
    {
        $secret = '123';
        $cachedSecret = '321';
        $userGuid = '999999999';
        $user->getGuid()->shouldBeCalled()->willReturn($userGuid);

        $this->redis->get("deferred-secret:$userGuid")
            ->shouldBeCalled()
            ->willReturn($cachedSecret);

        $this->redis->delete("deferred-secret:$userGuid")
            ->shouldBeCalled();

        $this->verify($secret, $user)->shouldBe(false);
    }
}
