<?php

namespace Spec\Minds\Core\Security;

use Minds\Core\Security\SignedUri;
use Minds\Entities\User;
use Minds\Core\Config;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SignedUriSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SignedUri::class);
    }

    public function it_sign_a_uri(Config $config)
    {
        $user = new User();
        $user->set('guid', 123);
        $user->set('username', 'phpspec');
        \Minds\Core\Session::setUser($user);

        $this->beConstructedWith(null, null, $config);
        $config->get('sessions')
            ->willReturn([
                'private_key' => 'priv-key'
            ]);

        $this->sign("https://minds-dev/foo")
            ->shouldContain("https://minds-dev/foo?jwtsig=");
    }

    public function it_should_verify_a_uri_was_signed(Config $config)
    {
        $this->beConstructedWith(null, null, $config);
        $config->get('sessions')
            ->willReturn([
                'private_key' => 'priv-key'
            ]);

        $uri = "https://minds-dev/foo?jwtsig=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE1NzI1NjY0MDAsInVyaSI6Imh0dHBzOlwvXC9taW5kcy1kZXZcL2ZvbyIsInVzZXJfZ3VpZCI6IjEyMyJ9.jqOq0k-E4h1I0PHnc_WkmWqXonRU4yWq_ymoOYoaDvc";
        $this->confirm($uri)
            ->shouldBe(true);
    }

    public function it_should_not_very_a_wrongly_signed_uri(Config $config)
    {
        $this->beConstructedWith(null, null, $config);
        $config->get('sessions')
            ->willReturn([
                'private_key' => 'priv-key'
            ]);

        $uri = "https://minds-dev/bar?jwtsig=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE1NzI1NjY0MDAsInVyaSI6Imh0dHBzOlwvXC9taW5kcy1kZXZcL2ZvbyIsInVzZXJfZ3VpZCI6IjEyMyJ9.jqOq0k-E4h1I0PHnc_WkmWqXonRU4yWq_ymoOYoaDvc";
        $this->confirm($uri)
            ->shouldBe(false);
    }
}
