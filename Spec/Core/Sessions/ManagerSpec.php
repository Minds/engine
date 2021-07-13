<?php

namespace Spec\Minds\Core\Sessions;

use DateTimeImmutable;
use Minds\Core\Sessions\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Sessions\Repository;
use Minds\Core\Sessions\Session;
use Minds\Core\Config;
use Minds\Core;
use Minds\Common\Cookie;
use Zend\Diactoros\ServerRequest;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Minds\Common\IpAddress;
use Minds\Entities\User;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_build_a_session_with_request(
        Repository $repository,
        Config $config,
        IpAddress $ipAddress
    ) {
        $jwtConfig = $this->getMockedJwtConfig();

        $this->beConstructedWith(
            $repository,
            $config,
            null,
            $jwtConfig,
            $ipAddress
        );

        $token = $jwtConfig
            ->builder()
                    //->issuedBy('spec.tests')
                    //->canOnlyBeUsedBy('spec.tests')
                    ->identifiedBy('mock_session_id')
                    ->withHeader('jti', 'mock_session_id')
                    ->expiresAt((new DateTimeImmutable())->setTimestamp(time() + 3600)) // 1 hour
                    ->withClaim('user_guid', 'user_1')
                    ->getToken($jwtConfig->signer(), $jwtConfig->signingKey())
                    ->toString();

        $request = new ServerRequest();

        $request = $request->withCookieParams([
            'minds_sess' => $token,
        ]);

        $session = new Session();
        $session
            ->setId('mock_session_id')
            ->setUserGuid('user_1')
            ->setToken($token)
            ->setExpires(time() + 3600);

        $repository->get('user_1', 'mock_session_id')
            ->shouldBeCalled()
            ->willReturn($session);

        // Confirm the ip is being set

        $ipAddress->get()
            ->willReturn('10.0.50.1');

        $repository->update(Argument::that(function ($session) {
            return $session->getIp() === '10.0.50.1';
        }), [ 'last_active', 'ip'])
            ->willReturn(true);

        $this->withRouterRequest($request);

        // Confirm the session was set
        $this->getSession()->getId()
            ->shouldBe($session->getId());
    }

    public function it_should_not_build_a_session_with_request_if_not_on_server(
        Repository $repository,
        Config $config
    ) {
        $jwtConfig = $this->getMockedJwtConfig();
        $this->beConstructedWith(
            $repository,
            $config,
            null,
            $jwtConfig,
            null
        );

        $token = $jwtConfig->builder()
                    //->issuedBy('spec.tests')
                    //->canOnlyBeUsedBy('spec.tests')
                    ->identifiedBy('mock_session_id')
                    ->withHeader('jti', 'mock_session_id')
                    ->expiresAt((new DateTimeImmutable())->setTimestamp(time() + 3600)) // 1 hour
                    ->withClaim('user_guid', 'user_1')
                    ->getToken($jwtConfig->signer(), $jwtConfig->signingKey())
                    ->toString();

        $request = new ServerRequest();

        $request = $request->withCookieParams([
            'minds_sess' => $token,
        ]);

        $session = new Session();
        $session
            ->setId('mock_session_id')
            ->setUserGuid('user_1')
            ->setToken($token)
            ->setExpires(time() + 3600);

        $repository->get('user_1', 'mock_session_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->withRouterRequest($request);

        // Confirm the session was set
        $this->getSession()->shouldBeNull();
    }

    public function it_should_not_build_a_session_with_request_if_forged_token(
        Repository $repository,
        Config $config
    ) {
        $jwtConfig = $this->getMockedJwtConfig();
        $this->beConstructedWith(
            $repository,
            $config,
            null,
            $jwtConfig,
            null
        );

        Core\Session::setUserByGuid(null);

        $token = $jwtConfig->builder()
                    //->issuedBy('spec.tests')
                    //->canOnlyBeUsedBy('spec.tests')
                    ->identifiedBy('mock_session_id')
                    ->withHeader('jti', 'mock_session_id')
                    ->expiresAt((new DateTimeImmutable())->setTimestamp(time() + 3600)) // 1 hour
                    ->withClaim('user_guid', 'user_1')
                    ->getToken($jwtConfig->signer(), $jwtConfig->signingKey())
                    ->toString();

        $request = new ServerRequest();

        $request = $request->withCookieParams([
            'minds_sess' => $token,
        ]);

        $this->withRouterRequest($request);

        // Confirm the session was set
        $this->getSession()->shouldBeNull();
    }

    public function it_should_save_session_to_client_and_server(
        Repository $repository,
        Cookie $cookie
    ) {
        $this->beConstructedWith(
            $repository,
            null,
            $cookie
        );

        $session = new Session();
        $session
            ->setId('mock_session_id')
            ->setUserGuid('user_1')
            ->setToken('token')
            ->setExpires(time() + 3600);

        $this->setSession($session);

        $repository->add($session);

        $cookie->setName('minds_sess')
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setValue('token')
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setExpire(time() + 3600)
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setSecure(true)
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setHttpOnly(true)
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setPath('/')
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->create()
            ->shouldBeCalled();

        $this->save();
    }

    public function it_should_delete_session_on_client_and_server(
        Repository $repository,
        Cookie $cookie
    ) {
        $this->beConstructedWith(
            $repository,
            null,
            $cookie
        );

        $session = new Session();
        $session
            ->setId('mock_session_id')
            ->setUserGuid('user_1')
            ->setToken('token')
            ->setExpires(time() + 3600);

        $this->setSession($session);

        $repository->delete($session)
        ->willReturn(true);

        $cookie->setName('minds_sess')
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setValue('')
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setExpire(time() - 3600)
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setSecure(true)
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setHttpOnly(true)
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->setPath('/')
            ->shouldBeCalled()
            ->willReturn($cookie);

        $cookie->create()
            ->shouldBeCalled();

        $this->delete();
    }

    public function it_should_delete_all_sessions_on_server(
        Repository $repository
    ) {
        $this->beConstructedWith(
            $repository
        );

        $user = new User();
        $user->guid = 1234567;

        $repository->deleteAll($user)
        ->willReturn(true);

        $this->deleteAll($user);
    }


    protected function getMockedJwtConfig()
    {
        return JWT\Configuration::forAsymmetricSigner(
            new Sha512,
            InMemory::plainText("
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEApvnChiEHxXmpMNaPTwdctkTDo9enXhHArO77yfLHZoB1J98B
7GZ7GF+W19yM+kJKgJudEmLw22YW8Ycr5Aenhl1JMhmGBpiY+XyMaPc4vWufrEfP
UVpjOAVqx+OpRGOogJx29K0MkqUESITj4gVn7BxKCOE7qNbXcYYAiTWot2ODIBZe
NZokm/9zrZ95jjOiqP/CL9PN+mYc6WeRr4w5EXCnkswu02Yzdtj5Xxyms+ur4ice
Oy9a6jE8kIqGfUPno/VdeJlnVMpV60QDkWtEyA4hI7SirLQ6AZQtQyIt0LzVGRGg
1u1iA/sRjGwB7dHWtc7JcG1rmp7xfVA7SznNwwIDAQABAoIBACJ8DJek9LTtBmtG
tLwumhAurXUGEdPUuMU+ahPwJwxdVVTRstT+6UdEXqPgMeFxlW9wNAVbF8FIGU7y
ircCea+/TmGhcdOk6lsERP9cp4Q/WO+8uO1lTH6CZ+Y2d3vfVSqSpeKsZp9Wo0bS
4zmHwkm6IfQpiCe7jy0r7qpnwZt2BUoubT7COG86efFFPfgEdxkIpEPrk3KNSHLY
I96NzyAd3NOzHTYWgART87gjcdMhmWyLjzhZDn5X01kZY1yJMQmKO+6SzM6Mbku2
CdjsfdVDFlOxuU1IB9+tYxLkdz0epnFMEiWlsE5KL3P724pRFEO+NoRP1guan35x
I1jD04kCgYEA3C1jeWDFmm1NoknDDLxWcqZ2UlfAGJBGvEzzjJCyhE73vFH3Lv/H
Qd10Mw65jGrXg5ILbZV1CxDSvsx4fKXj3QUPs82M3/XlJB64J1NfUy5+UC/vcQ8q
tLKWne2q0SoLbLugZrm7RwKTXGT4YdTOGxCaBRUIvsy0uG/4EHb/ILUCgYEAwiR3
eic/ke26GVfsQGLUv+95/1owWzYrzcepD2CtTWCmVRW/Sa6EZ9CCaXHmRX4uV78O
vQmp6qIT4bVhG94iyY/WJPjAnrkI2ahNCV8olYM5115BNmaHeaARzXEgi2UyOESU
ZgTDFOWN9NpfHN6dljfRXpI8qlF8igV7sRAgV5cCgYAZX/3D4lxDtO8qkfexww7v
fbHLQaO48P/F+dRj0dVRHEy+3m9vcjkDpUMcE0ldHn8iAbXhdkUb9l9jb+s+6lt9
gHTT0w+2S/+RjxzII3qr+oLCORQOYqIYWzCymM6D9qWEbYdJ74Pe5jQXhOd/Vug+
BEbL6SWt36fATd83/o7etQKBgDP33P+W1/5xG1rDXVtS2U5ThV2kP8N6ubkI1Clo
oJtQ3tVxz9WiYJEFkJM3SQObJj6YxxI1LwW+wwGtMsRp7vfzh8g3yh/yufrBgXWb
wlpbWTVcZqpwQZ1+CqXqvWJzAUFsoii455uFYz2C4ujwclCOun3NOW4CCAtOMnEQ
NwgbAoGBALPXHBmbxlZuMdwfPBMbZIbLnUeksXkFjIwxb6yestZe5vyAjobjMC9/
hLom+msoew3BaAjwWGpOuSs3U96U+THoVIFyG+LF7tCck8PKpY3n4vKb7908WDUX
aTpdB3sjEe8ov+al2kJYBSJcqbUmUMVCY7v0Zig2VlYMPjzn/icP
-----END RSA PRIVATE KEY-----
        "),
            InMemory::plainText("
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApvnChiEHxXmpMNaPTwdc
tkTDo9enXhHArO77yfLHZoB1J98B7GZ7GF+W19yM+kJKgJudEmLw22YW8Ycr5Aen
hl1JMhmGBpiY+XyMaPc4vWufrEfPUVpjOAVqx+OpRGOogJx29K0MkqUESITj4gVn
7BxKCOE7qNbXcYYAiTWot2ODIBZeNZokm/9zrZ95jjOiqP/CL9PN+mYc6WeRr4w5
EXCnkswu02Yzdtj5Xxyms+ur4iceOy9a6jE8kIqGfUPno/VdeJlnVMpV60QDkWtE
yA4hI7SirLQ6AZQtQyIt0LzVGRGg1u1iA/sRjGwB7dHWtc7JcG1rmp7xfVA7SznN
wwIDAQAB
-----END PUBLIC KEY-----
        ")
        );
    }
}
