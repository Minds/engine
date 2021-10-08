<?php

namespace Spec\Minds\Core\Security;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\Token;
use Minds\Core\Security\XSRF;
use Minds\Core\Session as SessionHandler;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Core\Sessions\Session;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class XSRFSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(null);
        SessionHandler::setUser(null);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(XSRF::class);
    }

    public function it_should_create_xsrf_token_with_sessionId_no_existing_cookie(
        SessionsManager $sessionsManager
    ) {
        // Prepare the request object
        $request = (new ServerRequest())
            ->withCookieParams([
                'minds_sess' => $this->mockJWTToken()->toString()
            ]);
        $this->setRequest($request);

        // Prepare the session manager mock
        $sessionsManager
            ->getSession()
            ->shouldBecalled()
            ->willReturn($this->mockSession());

        $sessionsManager
            ->setUser($this->mockUser())
            ->shouldBeCalled()
            ->willReturn($this->mockUser());
        SessionHandler::setUser($this->mockUser());

        $sessionsManager
            ->withRouterRequest($request)
            ->shouldBeCalled()
            ->willReturn($sessionsManager);

        $this->setSessionsManager($sessionsManager);

        // Perform actions
        $response = $this->setCookie();

        // Assert results
        $response->shouldBeEqualTo(true);
    }

    public function it_should_create_xsrf_token_without_sessionId_no_existing_cookie(
        SessionsManager $sessionsManager
    ) {
        // Prepare the request object
        $request = (new ServerRequest());
        $this->setRequest($request);

        // Perform actions
        $response = $this->setCookie();

        // Assert results
        $response->shouldBeEqualTo(true);
    }

    public function it_should_not_create_xsrf_token_when_cookie_exists_and_sessionId_in_token()
    {
        $request = (new ServerRequest())
            ->withCookieParams(
                [
                    'minds_sess' => $this->mockJWTToken()->toString(),
                    'XSRF-TOKEN' => 'randompart-sessionid'
                ]
            );
        $this->setRequest($request);

        SessionHandler::setUser($this->mockUser());

        // Perform actions
        $response = $this->setCookie();

        // Assert results
        $response->shouldBeEqualTo(false);
    }

    public function it_should_not_create_xsrf_token_when_cookie_exists_and_no_sessionId_in_token_and_no_session_active()
    {
        $request = (new ServerRequest())
            ->withCookieParams(
                [
                    'minds_sess' => $this->mockJWTToken()->toString(),
                    'XSRF-TOKEN' => 'randompart'
                ]
            );
        $this->setRequest($request);

        // Perform actions
        $response = $this->setCookie();

        // Assert results
        $response->shouldBeEqualTo(false);
    }

    private function mockUser() : User
    {
        $user =  new User();
        $user->set('guid', "123");
        $user->set('username', "phpspec");
        return $user;
    }

    private function mockSession() : Session
    {
        return (new Session())
            ->setId("123")
            ->setToken($this->mockJWTToken()->toString())
            ->setUserGuid('123');
    }

    private function mockJWTToken() : Token
    {
        $jwtConfig = $this->mockJWTConfig();
        return $jwtConfig
            ->builder()
            ->identifiedBy('123')
            ->withHeader('jti', '123')
            ->expiresAt(new \DateTimeImmutable('+30 days'))
            ->withClaim('user_guid', '123')
            ->getToken($jwtConfig->signer(), $jwtConfig->signingKey());
    }

    private function mockJWTConfig() : Configuration
    {
        return Configuration::forAsymmetricSigner(
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
