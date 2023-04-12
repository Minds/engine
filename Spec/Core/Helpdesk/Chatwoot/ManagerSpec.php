<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Helpdesk\Chatwoot;

use Minds\Core\Config\Config;
use Minds\Core\Helpdesk\Chatwoot\Manager;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $config;

    public function let(
        Config $config
    ) {
        $this->config = $config;
        $this->beConstructedWith($this->config);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Manager::class);
    }

    public function it_should_generate_a_hmac(User $user): void
    {
        $this->config->get('chatwoot')
            ->shouldBeCalled()
            ->willReturn([
                'signing_key' => 'abcd123456'
            ]);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->getUserHmac($user)->shouldBe('dfdefd9e4a12e4f34584fdbbf3eb5b25acb07502e69234fa4efd94c4e549c13b');
    }

    public function it_should_throw_an_exception_if_no_key_is_set(User $user): void
    {
        $this->config->get('chatwoot')
            ->shouldBeCalled()
            ->willReturn([]);

        $this->shouldThrow(
            new ServerErrorException('No signing key set for chatwoot')
        )->during('getUserHmac', [$user]);
    }
}
