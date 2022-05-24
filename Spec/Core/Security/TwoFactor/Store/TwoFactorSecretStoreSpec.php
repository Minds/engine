<?php

namespace Spec\Minds\Core\Security\TwoFactor\Store;

use PhpSpec\ObjectBehavior;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecretStore;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Entities\User;
use Prophecy\Argument;

class TwoFactorSecretStoreSpec extends ObjectBehavior
{
    /** @var PsrWrapper */
    protected $cache;

    public function let(PsrWrapper $cache)
    {
        $this->beConstructedWith($cache);
        $this->cache = $cache;
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(TwoFactorSecretStore::class);
    }

    public function it_should_get_untrusted_users_object(User $user)
    {
        $user->get('username')->shouldBeCalled()->willReturn('~username~');
        $user->get('salt')->shouldBeCalled()->willReturn('~salt~');

        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(false); // force false state so key is not random.

        $this->cache->get('3fea6dc82ff562c9ce8533e7c1d78abc33424a0b7201abe1fa6be8d45b6e8ca63a592007cafda3dc0422f05e1bb45dc0c159e8b2bae0f992ad6e78eb233eec29')
            ->shouldBeCalled()
            ->willReturn('{"_guid":"~guid~","ts":"~timestamp~","secret":"~secret~"}');

        $twoFactorSecretObject = $this->get($user);
        $twoFactorSecretObject->getGuid()->shouldBe('~guid~');
        $twoFactorSecretObject->getTimestamp()->shouldBe('~timestamp~');
        $twoFactorSecretObject->getSecret()->shouldBe('~secret~');
    }

    public function it_should_get_trusted_users_object(User $user)
    {
        $user->get('username')->shouldBeCalled()->willReturn('~username~');
        $user->get('salt')->shouldBeCalled()->willReturn('~salt~');

        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cache->get(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn('{"_guid":"~guid~","ts":"~timestamp~","secret":"~secret~"}');

        $twoFactorSecretObject = $this->get($user);
        $twoFactorSecretObject->getGuid()->shouldBe('~guid~');
        $twoFactorSecretObject->getTimestamp()->shouldBe('~timestamp~');
        $twoFactorSecretObject->getSecret()->shouldBe('~secret~');
    }

    public function it_should_return_null_if_no_object_is_found(User $user)
    {
        $user->get('username')->shouldBeCalled()->willReturn('~username~');
        $user->get('salt')->shouldBeCalled()->willReturn('~salt~');

        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cache->get(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn(null);

        $this->get($user)->shouldBe(null);
    }

    public function it_should_set(User $user)
    {
        $user->get('username')->shouldBeCalled()->willReturn('~username~');
        $user->get('salt')->shouldBeCalled()->willReturn('~salt~');
        $user->get('guid')->shouldBeCalled()->willReturn('~guid~');

        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cache->set(
            Argument::type('string'),
            Argument::type('string'),
            900
        )
            ->shouldBeCalled();

        $this->set($user, '~secret~');
    }

    public function it_should_delete()
    {
        $this->cache->delete('~key~')
            ->shouldBeCalled();

        $this->delete('~key~');
    }

    public function it_should_get_ttl_for_a_trusted_user(
        User $user
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->getTtl($user)->shouldBe(900);
    }

    public function it_should_get_ttl_for_an_untrusted_user(
        User $user
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->getTtl($user)->shouldBe(86400);
    }
}
