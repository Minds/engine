<?php

namespace Spec\Minds\Common;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Config\Config;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PseudonymousIdentifierSpec extends ObjectBehavior
{
    protected Collaborator $configMock;

    public function let(Config $configMock)
    {
        $this->beConstructedWith(null, $configMock);
        $this->configMock = $configMock;

        $configMock->get('sessions')
            ->willReturn([
                'private_key' => dirname(__FILE__) . '/spec-priv-key.pem',
            ]);
        $configMock->get('tenant_id')
            ->willReturn(null);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PseudonymousIdentifier::class);
    }

    public function it_should_generated_a_pseudonymous_identifier_from_a_password(User $user)
    {
        $user->getGuid()
            ->willReturn('123');
        $user->get('password')
            ->willReturn('$2y$10$JWASv0VBel4cdYxe4350.OZtrgI24rWYkon7O89Mt2OXNjPmw.aGC');
        $this
            ->setUser($user)
            ->generateWithPassword('hello-world')
            ->shouldBe("ci0o3kjj4ailhsvk5ofxtw");

        $user->getGuid()
            ->willReturn('456');
        $user->get('password')
            ->willReturn('$2y$10$C4eogYA1O4Z.mzHjjnszDOxTeYBscFGqowBDSTbzItHFpjDRHeEya');
        $this
            ->setUser($user)
            ->generateWithPassword('thisisaveryweakpassword')
            ->shouldBe("opfmnixllqh6lgcpq0w1w");

        $user->getGuid()
            ->willReturn('100000000000000063');
        $user->get('password')
            ->willReturn('$2y$10$FxaxEoD/gy10JgWrsIKOMO/ghRn9wnSwfynZ7wUiO/mjEPupybt8i');
        $this
            ->setUser($user)
            ->generateWithPassword('Pa$$w0rd')
            ->shouldBe("okmajpbhopbcqxwrsvgdeq");
    }

    public function it_should_return_id_based_on_cookie_value(User $user)
    {
        $_COOKIE['minds_pseudoid'] = "5058da52e5f35eab7329";

        $user->getGuid()
            ->willReturn('123');
        $this
            ->setUser($user)
            ->getId()->shouldBe("5058da52e5f35eab7329");
    }

    public function it_should_not_return_id_if_tenant()
    {
        $this->configMock->get('tenant_id')->willReturn(1);
        $this
            ->setUser(new User())
            ->getId()->shouldBe(null);
    }

    public function it_should_not_generate_id_from_password_if_tenant()
    {
        $this->configMock->get('tenant_id')->willReturn(1);
        $this
            ->setUser(new User())
            ->generateWithPassword('Pa$$w0rd')
            ->shouldBe(null);
    }
}
