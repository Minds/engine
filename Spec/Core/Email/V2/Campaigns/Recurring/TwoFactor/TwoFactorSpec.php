<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\TwoFactor;

use Minds\Core\Config\Config;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\TwoFactor\TwoFactor;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Guid;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class TwoFactorSpec extends ObjectBehavior
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
        $this->shouldHaveType(TwoFactor::class);
    }

    public function it_should_build_an_email_confirmation_email_for_a_non_tenant(
        User $user
    ): void {
        $userGuid = Guid::build();
        $code = '123456';
        $userUsername = 'username';
        $userName = 'User name';
        $userEmail = 'no-reply@minds.com';
        $siteName = 'Minds';
        $tenantId = null;
        $locale = 'EN';
        $isTrusted = false;

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

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
            
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn($isTrusted);

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->template->getTranslator()
            ->shouldBeCalled();

        $this->template->setLocale($locale)
            ->shouldBeCalled();

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.v2.verify.tpl')
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
        
        $this->template->set('code', $code)
            ->shouldBeCalled();

        $this->template->set('tracking', Argument::any())
            ->shouldBeCalled();

        $this->template->set('preheader', 'Verify your email to get started')
            ->shouldBeCalled();

        $this->template->set('title', '123456 is your verification code for Minds')
            ->shouldBeCalled();

        $this->template->set('hide_unsubscribe_link', true)
            ->shouldNotBeCalled();

        $this->tenantTemplateVariableInjector->inject($this->template)
            ->shouldNotBeCalled();

        $this->mailer->send(Argument::any(), true)->shouldBeCalled();

        $this->setCode($code)->setUser($user)->send();
    }

    public function it_should_build_an_email_confirmation_email_for_a_tenant(
        User $user
    ): void {
        $userGuid = Guid::build();
        $code = '123456';
        $userUsername = 'username';
        $userName = 'User name';
        $userEmail = 'no-reply@minds.com';
        $siteName = 'Test site';
        $tenantId = '123';
        $locale = 'EN';
        $isTrusted = false;

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

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
            
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn($isTrusted);

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->template->getTranslator()
            ->shouldBeCalled();

        $this->template->setLocale($locale)
            ->shouldBeCalled();

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.v2.verify.tpl')
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
        
        $this->template->set('code', $code)
            ->shouldBeCalled();

        $this->template->set('tracking', Argument::any())
            ->shouldBeCalled();

        $this->template->set('preheader', 'Verify your email to get started')
            ->shouldBeCalled();

        $this->template->set('title', '123456 is your verification code for Test site')
            ->shouldBeCalled();

        $this->template->set('hide_unsubscribe_link', true)
            ->shouldBeCalled();

        $this->tenantTemplateVariableInjector->inject($this->template)
            ->shouldBeCalled();

        $this->mailer->send(Argument::any(), true)->shouldBeCalled();

        $this->setCode($code)->setUser($user)->send();
    }

    public function it_should_build_an_two_factor_auth_email_for_a_non_tenant(
        User $user
    ): void {
        $userGuid = Guid::build();
        $code = '123456';
        $userUsername = 'username';
        $userName = 'User name';
        $userEmail = 'no-reply@minds.com';
        $siteName = 'Minds';
        $tenantId = null;
        $locale = 'EN';
        $isTrusted = true;

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

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
            
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn($isTrusted);

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->template->getTranslator()
            ->shouldBeCalled();

        $this->template->setLocale($locale)
            ->shouldBeCalled();

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.v2.2fa.tpl')
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
        
        $this->template->set('code', $code)
            ->shouldBeCalled();

        $this->template->set('tracking', Argument::any())
            ->shouldBeCalled();

        $this->template->set('preheader', 'Verify your action')
            ->shouldBeCalled();

        $this->template->set('title', '123456 is your verification code for Minds')
            ->shouldBeCalled();

        $this->template->set('hide_unsubscribe_link', true)
            ->shouldNotBeCalled();

        $this->tenantTemplateVariableInjector->inject($this->template)
            ->shouldNotBeCalled();

        $this->mailer->send(Argument::any(), true)->shouldBeCalled();

        $this->setCode($code)->setUser($user)->send();
    }

    public function it_should_build_an_two_factor_auth_email_for_a_tenant(
        User $user
    ): void {
        $userGuid = Guid::build();
        $code = '123456';
        $userUsername = 'username';
        $userName = 'User name';
        $userEmail = 'no-reply@minds.com';
        $siteName = 'Test site';
        $tenantId = '123';
        $locale = 'EN';
        $isTrusted = true;

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

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
            
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn($isTrusted);

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->template->getTranslator()
            ->shouldBeCalled();

        $this->template->setLocale($locale)
            ->shouldBeCalled();

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.v2.2fa.tpl')
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
        
        $this->template->set('code', $code)
            ->shouldBeCalled();

        $this->template->set('tracking', Argument::any())
            ->shouldBeCalled();

        $this->template->set('preheader', 'Verify your action')
            ->shouldBeCalled();

        $this->template->set('title', '123456 is your verification code for Test site')
            ->shouldBeCalled();

        $this->template->set('hide_unsubscribe_link', true)
            ->shouldBeCalled();

        $this->tenantTemplateVariableInjector->inject($this->template)
            ->shouldBeCalled();

        $this->mailer->send(Argument::any(), true)->shouldBeCalled();

        $this->setCode($code)->setUser($user)->send();
    }
}
