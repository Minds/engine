<?php

namespace Spec\Minds\Core\SSO;

use Exception;
use Lcobucci\JWT\Token;
use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Core\Sessions\Session;
use Minds\Core\SSO\Delegates;
use Minds\Core\SSO\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var abstractCacher */
    protected $cache;

    /** @var Jwt */
    protected $jwt;

    /** @var SessionsManager */
    protected $sessions;

    /** @var Delegates\ProDelegate */
    protected $proDelegate;

    public function let(
        Config $config,
        abstractCacher $cache,
        Jwt $jwt,
        SessionsManager $sessions,
        Delegates\ProDelegate $proDelegate
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->jwt = $jwt;
        $this->sessions = $sessions;
        $this->proDelegate = $proDelegate;

        $this->config->get('oauth')
            ->willReturn([
                'encryption_key' => '~key~'
            ]);

        $this->beConstructedWith(
            $config,
            $cache,
            $jwt,
            $sessions,
            $proDelegate
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_generate_token(
        Session $session,
        Token $token
    ) {
        $this->proDelegate->isAllowed('phpspec.test')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->sessions->getSession()
            ->shouldBeCalled()
            ->willReturn($session);

        $session->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $session->getToken()
            ->shouldBeCalled()
            ->willReturn($token);

        $token->toString()
            ->willReturn('~token~');

        $this->jwt->randomString()
            ->shouldBeCalled()
            ->willReturn('~random~');

        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $ssoKey = sprintf(
            "sso:%s:%s:%s",
            'phpspec.test',
            hash('sha256', '~key~~token~'),
            '~random~'
        );

        $this->jwt->encode([
            'key' => $ssoKey,
            'domain' => 'phpspec.test'
        ], Argument::type('int'), Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn('~jwt~');

        $this->cache->set($ssoKey, '~token~', Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setDomain('phpspec.test')
            ->generateToken()
            ->shouldReturn('~jwt~');
    }

    public function it_should_not_generate_a_token_if_logged_out()
    {
        $this->proDelegate->isAllowed('phpspec.test')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->sessions->getSession()
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->setDomain('phpspec.test')
            ->generateToken()
            ->shouldReturn(null);
    }

    public function it_should_authorize()
    {
        $this->proDelegate->isAllowed('phpspec.test')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->decode('~jwt~')
            ->shouldBeCalled()
            ->willReturn([
                'key' => 'sso:key',
                'domain' => 'phpspec.test'
            ]);

        $this->cache->get('sso:key')
            ->shouldBeCalled()
            ->willReturn('~token~');

        $this->sessions->withString('~token~')
            ->shouldBeCalled()
            ->willReturn($this->sessions);

        $this->sessions->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cache->destroy('sso:key')
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setDomain('phpspec.test')
            ->shouldNotThrow(Exception::class)
            ->duringAuthorize('~jwt~');
    }

    public function it_should_not_authorize_if_domain_mismatches()
    {
        $this->proDelegate->isAllowed('other-phpspec.test')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->decode('~jwt~')
            ->shouldBeCalled()
            ->willReturn([
                'key' => 'sso:key',
                'domain' => 'phpspec.test'
            ]);

        $this->sessions->withString(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setDomain('other-phpspec.test')
            ->shouldThrow(new Exception('Domain mismatch'))
            ->duringAuthorize('~jwt~');
    }
}
