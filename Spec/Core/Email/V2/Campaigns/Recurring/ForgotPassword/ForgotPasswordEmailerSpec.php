<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\ForgotPassword;

use Minds\Core\Config\Config;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Campaigns\Recurring\ForgotPassword\ForgotPasswordEmailer;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Guid;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ForgotPasswordEmailerSpec extends ObjectBehavior
{
    private Collaborator $template;
    private Collaborator $mailer;
    private Collaborator $config;
    private Collaborator $tenantTemplateVariableInjector;

    public function let(
        Template $template,
        Mailer $mailer,
        Config $config,
        TenantTemplateVariableInjector $tenantTemplateVariableInjector
    ) {
        $this->beConstructedWith(
            $template,
            $mailer,
            $config,
            $tenantTemplateVariableInjector
        );

        $this->template = $template;
        $this->mailer = $mailer;
        $this->config = $config;
        $this->tenantTemplateVariableInjector = $tenantTemplateVariableInjector;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ForgotPasswordEmailer::class);
    }

    public function it_should_build_an_email_for_a_non_tenant(
        User $user
    ): void {
        $userGuid = 1234567890123456;
        $code = '123456';
        $userUsername = 'username';
        $userName = 'User name';
        $userEmail = 'no-reply@minds.com';
        $siteName = 'Test site';
        $siteUrl = 'https://test.minds.com/';
        $tenantId = null;
        $locale = 'EN';

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->getLanguage()
            ->shouldBeCalled()
            ->willReturn($locale);

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

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->template->setLocale($locale)
            ->shouldBeCalled();

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.v2.tpl')
            ->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('site_name', $siteName)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();
        
        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('tracking', Argument::any())
            ->shouldBeCalled();

        $this->template->set('preheader', 'Reset your password by clicking this link.')
            ->shouldBeCalled();

        $this->template->set('title', 'Password reset')
            ->shouldBeCalled();

        $this->template->set('headerText', 'Reset your password')
            ->shouldBeCalled();

        $this->template->set('bodyText', 'Use this link to reset your password on ' . $siteName . '. If you did not request to reset your password, please disregard this message.')
            ->shouldBeCalled();

        $this->template->set('hide_unsubscribe_link', true)
            ->shouldNotBeCalled();

        $this->tenantTemplateVariableInjector->inject($this->template)
            ->shouldNotBeCalled();

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->mailer->queue(Argument::any(), true)->shouldBeCalled();

        $this->setCode($code)->setUser($user)->send();
    }

    public function it_should_build_an_email_for_a_tenant(
        User $user
    ): void {
        $userGuid = 1234567890123456;
        $code = '123456';
        $userUsername = 'username';
        $userName = 'User name';
        $userEmail = 'no-reply@minds.com';
        $siteName = 'Test site';
        $siteUrl = 'https://test.minds.com/';
        $tenantId = 123;
        $locale = 'EN';

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->getLanguage()
            ->shouldBeCalled()
            ->willReturn($locale);

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

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->template->setLocale($locale)
            ->shouldBeCalled();

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.v2.tpl')
            ->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('site_name', $siteName)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();
        
        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('tracking', Argument::any())
            ->shouldBeCalled();

        $this->template->set('preheader', 'Reset your password by clicking this link.')
            ->shouldBeCalled();

        $this->template->set('title', 'Password reset')
            ->shouldBeCalled();

        $this->template->set('headerText', 'Reset your password')
            ->shouldBeCalled();

        $this->template->set('bodyText', 'Use this link to reset your password on ' . $siteName . '. If you did not request to reset your password, please disregard this message.')
            ->shouldBeCalled();

        $this->template->set('hide_unsubscribe_link', true)
            ->shouldBeCalled();

        $this->tenantTemplateVariableInjector->inject($this->template)
            ->shouldBeCalled()
            ->willReturn($this->template);

        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->mailer->queue(Argument::any(), true)->shouldBeCalled();

        $this->setCode($code)->setUser($user)->send();
    }
}
