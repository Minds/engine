<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Security\ForgotPassword\Services;

use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Security\ForgotPassword\Cache\ForgotPasswordCache;
use Minds\Core\Security\ForgotPassword\Services\ForgotPasswordService;
use Minds\Core\Email\V2\Campaigns\Recurring\ForgotPassword\ForgotPasswordEmailer;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Audit\Services\AuditService;
use Minds\Core\Sessions\CommonSessions\Manager as CommonSessionsManager;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ForgotPasswordServiceSpec extends ObjectBehavior
{
    private Collaborator $cacheMock;
    private Collaborator $forgotPasswordEmailerMock;
    private Collaborator $commonSessionsManagerMock;
    private Collaborator $sessionsManagerMock;
    private Collaborator $saveActionMock;
    private Collaborator $aclMock;
    private Collaborator $auditServiceMock;
    private Collaborator $oidcUserServiceMock;

    public function let(
        ForgotPasswordCache $cacheMock,
        ForgotPasswordEmailer $forgotPasswordEmailerMock,
        CommonSessionsManager $commonSessionsManagerMock,
        SessionsManager $sessionsManagerMock,
        SaveAction $saveActionMock,
        ACL $aclMock,
        AuditService $auditServiceMock,
        OidcUserService $oidcUserServiceMock,
    ): void {
        $this->cacheMock = $cacheMock;
        $this->forgotPasswordEmailerMock = $forgotPasswordEmailerMock;
        $this->commonSessionsManagerMock = $commonSessionsManagerMock;
        $this->sessionsManagerMock = $sessionsManagerMock;
        $this->saveActionMock = $saveActionMock;
        $this->aclMock = $aclMock;
        $this->auditServiceMock = $auditServiceMock;
        $this->oidcUserServiceMock = $oidcUserServiceMock;

        $this->beConstructedWith(
            $cacheMock,
            $forgotPasswordEmailerMock,
            $commonSessionsManagerMock,
            $sessionsManagerMock,
            $saveActionMock,
            $aclMock,
            $auditServiceMock,
            $oidcUserServiceMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ForgotPasswordService::class);
    }

    // request

    public function it_should_request_password_reset_using_cached_code(
        User $user
    ): void {
        $userGuid = '1234567890123456';
        $code = 'code';

        $user->getGuid()->willReturn($userGuid);

        $this->oidcUserServiceMock->isOidcUser($user)
            ->willReturn(false);

        $this->cacheMock->get($userGuid)->willReturn($code);

        $this->cacheMock->set($userGuid, $code)->shouldBeCalled()->willReturn(true);

        $this->forgotPasswordEmailerMock->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->forgotPasswordEmailerMock);

        $this->forgotPasswordEmailerMock->setCode($code)
            ->shouldBeCalled()
            ->willReturn($this->forgotPasswordEmailerMock);

        $this->forgotPasswordEmailerMock->send()->shouldBeCalled();

        $this->request($user)->shouldReturn(true);
    }

    // reset

    public function it_should_reset_using_generated_code(): void
    {
        $code = 'code';
        $password = 'password';
        $userGuid = '1234567890123456';

        $user = new User();
        $user->set('guid', $userGuid);
        $user->set('password_reset_code', $code);

        $this->oidcUserServiceMock->isOidcUser($user)
            ->willReturn(false);

        $this->cacheMock->get($userGuid)
            ->shouldBeCalled()
            ->willReturn($code);

        $this->saveActionMock->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->saveActionMock->withMutatedAttributes([
            'password',
            'password_reset_code'
        ])
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->saveActionMock->save()->shouldBeCalled();

        $this->cacheMock->delete($userGuid)->shouldBeCalled();

        $this->commonSessionsManagerMock->deleteAll($user)
            ->shouldBeCalled();

        $this->sessionsManagerMock->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->sessionsManagerMock);

        $this->sessionsManagerMock->createSession()
            ->shouldBeCalled()
            ->willReturn($this->sessionsManagerMock);

        $this->sessionsManagerMock->save()
            ->shouldBeCalled();

        $this->reset($user, $code, $password)->shouldReturn(true);
    }

    public function it_should_not_reset_using_generated_code_when_code_does_not_match_user_code(): void
    {
        $code = 'code';
        $invalidCode = 'invalidCode';
        $password = 'password';
        $userGuid = '1234567890123456';

        $user = new User();
        $user->set('guid', $userGuid);
        $user->set('password_reset_code', $invalidCode);

        $this->oidcUserServiceMock->isOidcUser($user)
            ->willReturn(false);

        $this->cacheMock->get($userGuid)
            ->shouldBeCalled()
            ->willReturn($code);

        $this->saveActionMock->setEntity($user)
            ->shouldNotBeCalled();

        $this->shouldThrow(new UserErrorException("Invalid reset code"))
            ->during('reset', [$user, $invalidCode, $password]);
    }

    public function it_should_not_reset_using_generated_code_when_code_does_not_match_cached_code(): void
    {
        $code = 'code';
        $invalidCode = 'invalidCode';
        $password = 'password';
        $userGuid = '1234567890123456';

        $user = new User();
        $user->set('guid', $userGuid);
        $user->set('password_reset_code', $code);

        $this->oidcUserServiceMock->isOidcUser($user)
            ->willReturn(false);

        $this->cacheMock->get($userGuid)
            ->shouldBeCalled()
            ->willReturn($invalidCode);

        $this->shouldThrow(new UserErrorException("Invalid reset code"))
            ->during('reset', [$user, $invalidCode, $password]);
    }

    public function it_should_not_request_if_an_oidc_user()
    {
        $user = new User();

        $this->oidcUserServiceMock->isOidcUser($user)
            ->willReturn(true);

        $this->shouldThrow(ForbiddenException::class)->duringRequest($user);
    }

    public function it_should_not_reset_if_an_oidc_user()
    {
        $user = new User();

        $this->oidcUserServiceMock->isOidcUser($user)
            ->willReturn(true);

        $this->shouldThrow(ForbiddenException::class)->duringReset($user, 'code', 'password');
    }
}
