<?php

namespace Spec\Minds\Core\Sessions\CommonSessions;

use DateTimeImmutable;
use Minds\Core\OAuth\Entities\AccessTokenEntity;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Core\OAuth\Managers\AccessTokenManager as OAuthManager;
use Minds\Core\Sessions\CommonSessions\CommonSession;
use Minds\Core\Sessions\CommonSessions\Manager;
use Minds\Core\Sessions\Session;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var SessionsManager */
    private $sessionsManager;

    /** @var OAuthManager */
    private $oauthManager;

    public function let(SessionsManager $sessionsManager, OAuthManager $oauthManager)
    {
        $this->beConstructedWith($sessionsManager, $oauthManager);
        $this->sessionsManager = $sessionsManager;
        $this->oauthManager = $oauthManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_all_sessions_for_user()
    {
        $user = new User();

        $this->sessionsManager->getList($user)
            ->willReturn([
                (new Session())
                    ->setId('id-1')
                    ->setUserGuid('123')
                    ->setIp('127.0.0.2')
                    ->setExpires(time())
                    ->setLastActive(strtotime('16th March 2021')),
                (new Session())
                    ->setId('id-2')
                    ->setUserGuid('123')
                    ->setIp('127.0.0.2')
                    ->setExpires(time())
                    ->setLastActive(strtotime('16th April 2021')),
                (new Session())
                    ->setId('id-3')
                    ->setUserGuid('123')
                    ->setIp('127.0.0.2')
                    ->setExpires(time())
                    ->setLastActive(strtotime('16th February 2021')),
            ]);

        $accessToken1 = new AccessTokenEntity();
        $accessToken1->setIdentifier('token-1');
        $accessToken1->setUserIdentifier('123');
        $accessToken1->setIp('128.0.0.5');
        $accessToken1->setExpiryDateTime(new DateTimeImmutable());
        $accessToken1->setLastActive(strtotime('25th February 2021'));

        $this->oauthManager->getList($user)
            ->willReturn([
                $accessToken1,
            ]);

        $sessions = $this->getAll($user);

        // Test the ordering
        $sessions[0]->getId()->shouldBe('id-2');
        $sessions[1]->getId()->shouldBe('id-1');
        $sessions[2]->getId()->shouldBe('token-1');

        // Test access token has correct values
        $sessions[2]->getPlatform()->shouldBe('app');
        $sessions[2]->getIp()->shouldBe('128.0.0.5');

        // Test session has correct values
        $sessions[0]->getPlatform()->shouldBe('browser');
        $sessions[0]->getIp()->shouldBe('127.0.0.2');
    }

    public function it_should_delete_via_common_session()
    {
        $commonSession = new CommonSession();
        $commonSession->setId('id-1');
        $commonSession->setUserGuid('123');
        $commonSession->setPlatform('browser');

        $this->sessionsManager->delete(Argument::that(function ($session) {
            return $session->getUserGuid() === '123'
                && $session->getId() === 'id-1';
        }))
            ->willReturn(true);

        $this->delete($commonSession)->shouldBe(true);
    }

    public function it_should_delete_oauth_via_common_session()
    {
        $commonSession = new CommonSession();
        $commonSession->setId('token-1');
        $commonSession->setUserGuid('123');
        $commonSession->setPlatform('app');

        $this->oauthManager->delete(Argument::that(function ($token) {
            return $token->getIdentifier() === 'token-1'
                && $token->getUserIdentifier() ===  '123';
        }))
            ->willReturn(true);

        $this->delete($commonSession)->shouldBe(true);
    }

    public function it_should_delete_all_via_common_session()
    {
        $user = new User();
        $user->guid = '1234567';

        $this->sessionsManager->deleteAll(Argument::that(function ($user) {
            return $user->getGuid() === '1234567';
        }))
            ->willReturn(true);

        $this->oauthManager->deleteAll(Argument::that(function ($user) {
            return $user->getGuid() === '1234567';
        }))
            ->willReturn(true);

        $this->deleteAll($user)->shouldBe(true);
    }
}
