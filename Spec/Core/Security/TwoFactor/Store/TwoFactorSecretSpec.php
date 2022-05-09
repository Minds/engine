<?php

namespace Spec\Minds\Core\Security\TwoFactor\Store;

use PhpSpec\ObjectBehavior;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecret;

class TwoFactorSecretSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(TwoFactorSecret::class);
    }

    public function it_should_output_values_as_json()
    {
        $this->setGuid('~guid~');
        $this->setTimestamp('~timestamp~');
        $this->setSecret('~secret~');

        $this->toJson()
            ->shouldBe('{"_guid":"~guid~","ts":"~timestamp~","secret":"~secret~"}');
    }
}
