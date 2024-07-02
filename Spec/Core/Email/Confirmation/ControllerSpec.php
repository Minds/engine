<?php

namespace Spec\Minds\Core\Email\Confirmation;

use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;
use Minds\Core\Email\Confirmation\Controller;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome\TenantUserWelcomeEmailer;
use Minds\Core\Log\Logger;
use Minds\Core\Security\TwoFactor\Manager;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Entities\User;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $managerMock;

    /** @var TenantUserWelcomeEmailer */
    protected $tenantUserWelcomeEmailerMock;

    /** @var Config */
    protected $configMock;
 
    /** @var Logger */
    protected $loggerMock;

    public function let(
        Manager $manager,
        TenantUserWelcomeEmailer $tenantUserWelcomeEmailer,
        Config $config,
        Logger $logger
    ) {
        $this->managerMock = $manager;
        $this->tenantUserWelcomeEmailerMock = $tenantUserWelcomeEmailer;
        $this->configMock = $config;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $this->managerMock,
            $this->tenantUserWelcomeEmailerMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_is_should_call_gatekeeper_to_confirm_email_and_return_success_if_passed(
        ServerRequest $request,
        User $user
    ) {
        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->managerMock->gatekeeper($user, Argument::any())
            ->shouldBeCalled();

        $jsonResponse = $this->confirmEmail($request);

        $json = $jsonResponse->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success'
        ]));
    }

    public function it_is_should_call_gatekeeper_to_confirm_email_and_NOT_return_success_if_error_thrown(
        ServerRequest $request,
        User $user
    ) {
        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->managerMock->gatekeeper($user, Argument::any())
            ->shouldBeCalled()
            ->willThrow(new TwoFactorRequiredException());

        $this->shouldThrow(TwoFactorRequiredException::class)
            ->during('confirmEmail', [$request]);
    }

    public function it_should_verify_code_for_non_tenants(
        ServerRequest $request,
        User $user
    ) {
        $code = '1234';
        $twoFactorKey = '~key~';
        $tenantId = 123;

        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getHeader('X-MINDS-2FA-CODE')
            ->shouldBeCalled()
            ->willReturn([$code]);

        $request->getHeader('X-MINDS-EMAIL-2FA-KEY')
            ->shouldBeCalled()
            ->willReturn($twoFactorKey);

        $this->managerMock->authenticateEmailTwoFactor($user, $code)
            ->shouldBeCalled();

        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->tenantUserWelcomeEmailerMock->setUser($user)
            ->shouldNotBeCalled();

        $this->tenantUserWelcomeEmailerMock->queue($user)
            ->shouldNotBeCalled();

        $jsonResponse = $this->verifyCode($request);

        $json = $jsonResponse->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success'
        ]));
    }

    public function it_should_verify_code_for_tenants(
        ServerRequest $request,
        User $user
    ) {
        $code = '1234';
        $twoFactorKey = '~key~';
        $tenantId = 123;

        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getHeader('X-MINDS-2FA-CODE')
            ->shouldBeCalled()
            ->willReturn([$code]);

        $request->getHeader('X-MINDS-EMAIL-2FA-KEY')
            ->shouldBeCalled()
            ->willReturn($twoFactorKey);

        $this->managerMock->authenticateEmailTwoFactor($user, $code)
            ->shouldBeCalled();

        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->tenantUserWelcomeEmailerMock->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->tenantUserWelcomeEmailerMock);

        $this->tenantUserWelcomeEmailerMock->queue($user)
            ->shouldBeCalled();

        $jsonResponse = $this->verifyCode($request);

        $json = $jsonResponse->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success'
        ]));
    }
}
