<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\MobileAppPreviewReady;

use Minds\Core\Config\Config;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Campaigns\Recurring\MobileAppPreviewReady\MobileAppPreviewReadyEmailer;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileAppPreviewQRCodeService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class MobileAppPreviewReadyEmailerSpec extends ObjectBehavior
{
    private Collaborator $templateMock;
    private Collaborator $mailerMock;
    private Collaborator $configMock;
    private Collaborator $tenantTemplateVariableInjectorMock;
    private Collaborator $mobileAppPreviewQRCodeServiceMock;
    private Collaborator $loggerMock;
    private Collaborator $managerMock;

    public function let(
        Template $templateMock,
        Mailer $mailerMock,
        Config $configMock,
        TenantTemplateVariableInjector $tenantTemplateVariableInjectorMock,
        MobileAppPreviewQRCodeService $mobileAppPreviewQRCodeServiceMock,
        Logger $loggerMock,
        Manager $managerMock
    ) {
        $this->beConstructedWith(
            $templateMock,
            $mailerMock,
            $configMock,
            $tenantTemplateVariableInjectorMock,
            $mobileAppPreviewQRCodeServiceMock,
            $loggerMock,
            $managerMock
        );

        $this->templateMock = $templateMock;
        $this->mailerMock = $mailerMock;
        $this->configMock = $configMock;
        $this->tenantTemplateVariableInjectorMock = $tenantTemplateVariableInjectorMock;
        $this->mobileAppPreviewQRCodeServiceMock = $mobileAppPreviewQRCodeServiceMock;
        $this->loggerMock = $loggerMock;
        $this->managerMock = $managerMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(MobileAppPreviewReadyEmailer::class);
    }

    public function it_should_build_an_email(
        User $user
    ): void {
        $userGuid = 1234567890123456;
        $userUsername = 'username';
        $userName = 'User name';
        $userEmail = 'no-reply@minds.com';
        $siteUrl = 'https://minds.com/';
        $tenantId = 123;

        $this->managerMock->isSubscribed(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->get('enabled')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->get('banned')
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);
        
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->get('username')
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($userName);

        $this->configMock->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->templateMock->clear()->shouldBeCalled()->willReturn([]);
        $this->templateMock->setTemplate('default.v2.tpl')->shouldBeCalled();
        $this->templateMock->setBody('./template.tpl')->shouldBeCalled();
        $this->templateMock->set('headerText', "Your mobile app preview is ready")->shouldBeCalled();
        $this->templateMock->set('preheader', "Your mobile app preview is ready")->shouldBeCalled();
        $this->templateMock->set('qrCodeImgSrc', "{$siteUrl}api/v3/multi-tenant/mobile-configs/qr-code")->shouldBeCalled();
        $this->templateMock->set('mobileDeepLinkUrl', Argument::any())->shouldBeCalled();
        $this->templateMock->set('user', $user)->shouldBeCalled();
        $this->templateMock->set('username', $userUsername)->shouldBeCalled();
        $this->templateMock->set('email', $userEmail)->shouldBeCalled();
        $this->templateMock->set('guid', $userGuid)->shouldBeCalled();
        $this->templateMock->set('campaign', 'with')->shouldBeCalled();
        $this->templateMock->set('topic', 'mobile_app_preview_ready')->shouldBeCalled();
        $this->templateMock->set(Argument::type('string'), Argument::type('string'))->shouldBeCalled();

        $this->tenantTemplateVariableInjectorMock->inject($this->templateMock)
            ->shouldBeCalled()
            ->willReturn($this->templateMock);

        $this->mailerMock->send(Argument::that(function ($message) use ($userEmail) {
            return $message->getSubject() === "Your mobile app preview is ready";
        }))->shouldBeCalled();

        $this->managerMock->saveCampaignLog(Argument::any())
            ->shouldBeCalled();

        $this->setUser($user)->send();
    }
}
