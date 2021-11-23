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
        $this
            ->setUser($user)
            ->generateWithPassword('hello-world')
            ->shouldBe("4acf10598d44c62e45ace11515b2a36ef42af2109a52008afc5e7c35646c71d1"); // hash('sha256', '123hello-world')
    }

    public function it_should_return_id_based_on_cookie_value(User $user)
    {
        $_COOKIE['minds_psudeoid'] = hash('sha256', '123hello-world');

        $user->getGuid()
            ->willReturn('123');
        $this
            ->setUser($user)
            ->getId()->shouldBe("4acf10598d44c62e45ace11515b2a36ef42af2109a52008afc5e7c35646c71d1");
    }
}
