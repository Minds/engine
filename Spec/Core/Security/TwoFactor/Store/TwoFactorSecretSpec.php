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

    public function it_should_output_json_serializable_object()
    {
        $this->setGuid('~guid~');
        $this->setTimestamp('~timestamp~');
        $this->setSecret('~secret~');

        $serializableObj = $this->jsonSerialize();
        $serializableObj->shouldBe([
            '_guid' => "~guid~",
            'ts' => "~timestamp~",
            'secret' => "~secret~"
        ]);
    }
}
