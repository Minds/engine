<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Security\ForgotPassword\Cache;

use Minds\Core\Security\ForgotPassword\Cache\ForgotPasswordCache;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\SimpleCache\CacheInterface;

class ForgotPasswordCacheSpec extends ObjectBehavior
{
    private Collaborator $cacheMock;

    public function let(CacheInterface $cacheMock): void
    {
        $this->cacheMock = $cacheMock;
        $this->beConstructedWith($cacheMock);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ForgotPasswordCache::class);
    }

    public function it_should_get_code(): void
    {
        $userGuid = 123;
        $code = 'code';

        $this->cacheMock->get("forgot-password:{$userGuid}")
            ->shouldBeCalled()
            ->willReturn($code);

        $this->get($userGuid)->shouldReturn($code);
    }

    public function it_should_return_null_when_code_is_not_set(): void
    {
        $userGuid = 123;

        $this->cacheMock->get("forgot-password:{$userGuid}")
            ->shouldBeCalled()
            ->willReturn(false);

        $this->get($userGuid)->shouldReturn(null);
    }

    public function it_should_set_code(): void
    {
        $userGuid = 123;
        $code = 'code';

        $this->cacheMock->set("forgot-password:{$userGuid}", $code, 86400)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->set($userGuid, $code)->shouldReturn(true);
    }

    public function it_should_delete_code(): void
    {
        $userGuid = 123;

        $this->cacheMock->delete("forgot-password:{$userGuid}")
            ->shouldBeCalled();

        $this->delete($userGuid)->shouldReturn($this);
    }
}
