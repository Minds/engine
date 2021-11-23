<?php

namespace Spec\Minds\Common;

use Minds\Common\PseudonymousIdentifier;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class PseudonymousIdentifierSpec extends ObjectBehavior
{
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
            ->shouldBe("cb0d93f3f30d315e49a1c9d4ed2c1fbe"); // echo hash_hmac('md5', '123', 'hello-world$2y$10$JWASv0VBel4cdYxe4350.OZtrgI24rWYkon7O89Mt2OXNjPmw.aGC');
    }

    public function it_should_return_id_based_on_cookie_value(User $user)
    {
        $_COOKIE['minds_psudeoid'] = hash_hmac('md5', '123', 'hello-world$2y$10$JWASv0VBel4cdYxe4350.OZtrgI24rWYkon7O89Mt2OXNjPmw.aGC');

        $user->getGuid()
            ->willReturn('123');
        $this
            ->setUser($user)
            ->getId()->shouldBe("cb0d93f3f30d315e49a1c9d4ed2c1fbe");
    }
}
