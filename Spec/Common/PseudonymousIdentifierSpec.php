<?php

namespace Spec\Minds\Common;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Config\Config;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class PseudonymousIdentifierSpec extends ObjectBehavior
{
    public function let(Config $config)
    {
        $this->beConstructedWith(null, $config);

        $config->get('sessions')
            ->willReturn([
                'private_key' => dirname(__FILE__) . '/spec-priv-key.pem',
            ]);
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
            ->shouldBe("b231c25a79a13bdd05d1fc");

        $user->getGuid()
            ->willReturn('456');
        $user->get('password')
            ->willReturn('$2y$10$C4eogYA1O4Z.mzHjjnszDOxTeYBscFGqowBDSTbzItHFpjDRHeEya');
        $this
            ->setUser($user)
            ->generateWithPassword('thisisaveryweakpassword')
            ->shouldBe("a22ede5d5fcc329e667025");

        $user->getGuid()
            ->willReturn('100000000000000063');
        $user->get('password')
            ->willReturn('$2y$10$FxaxEoD/gy10JgWrsIKOMO/ghRn9wnSwfynZ7wUiO/mjEPupybt8i');
        $this
            ->setUser($user)
            ->generateWithPassword('Pa$$w0rd')
            ->shouldBe("2fe778b1ad9b77aef0da00");
    }

    public function it_should_return_id_based_on_cookie_value(User $user)
    {
        $_COOKIE['minds_psudeoid'] = "5058da52e5f35eab7329";

        $user->getGuid()
            ->willReturn('123');
        $this
            ->setUser($user)
            ->getId()->shouldBe("5058da52e5f35eab7329");
    }
}
