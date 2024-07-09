<?php

namespace Spec\Minds\Core\Authentication\PersonalApiKeys\Services;

use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyHashingService;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PersonalApiKeyHashingServiceSpec extends ObjectBehavior
{
    private Collaborator $configMock;

    public function let(Config $configMock)
    {
        $this->beConstructedWith($configMock);
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PersonalApiKeyHashingService::class);
    }

    public function it_should_generate_a_personal_api_key()
    {
        $secret = $this->generateSecret();
        $secret->shouldStartWith('pak_');
    }

    public function it_should_hash_secret()
    {
        $privKey = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/Common/spec-priv-key.pem';
        $this->configMock->get('sessions')->willReturn([
            'private_key' => $privKey
        ]);

        $secret = 'pak_32b6801e5da0bae8fc7ce3d9d1929c5c5459382e56068893b03511bf5d5b0a9a';
        $hashSecret = $this->hashSecret($secret);
      
        $hashSecret->shouldBe(hash_hmac('sha512', '32b6801e5da0bae8fc7ce3d9d1929c5c5459382e56068893b03511bf5d5b0a9a', file_get_contents($privKey)));
    }
}
