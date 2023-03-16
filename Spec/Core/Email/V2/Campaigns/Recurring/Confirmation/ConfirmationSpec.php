<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\Confirmation;

use Minds\Core\Email\Confirmation\Url as ConfirmationUrl;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\Confirmation\Confirmation;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\I18n\Translator;
use Minds\Core\Pro;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ConfirmationSpec extends ObjectBehavior
{
    /** @var Collaborator */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var ConfirmationUrl */
    protected $confirmationUrl;

    /** @var Pro\Domain */
    protected $proDomain;

    public function let(Template $template, Mailer $mailer, ConfirmationUrl $confirmationUrl, Pro\Domain $proDomain)
    {
        $this->template = $template;
        $this->mailer = $mailer;
        $this->confirmationUrl = $confirmationUrl;
        $this->proDomain = $proDomain;
        $this->beConstructedWith($this->template, $mailer, $confirmationUrl, $proDomain);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Confirmation::class);
    }

    public function it_should_build_a_normal_confirmation_email(
        User $user,
        Translator $translator
    ): void {
        $user->getGUID()
            ->willReturn('123');
        $user->get('guid')
            ->willReturn('123');
        $user->get('username')
            ->willReturn('mark');
        $user->get('name')
            ->willReturn('mark');
        $user->getLanguage()
            ->willReturn('en');
        $user->getEmail()
            ->willReturn('mark@minds.com');

        $this->setUser($user);

        $translator->trans(Argument::type('string'))
            ->willReturn("");

        $this->template->getTranslator()
            ->willReturn($translator);

        $this->template->setTemplate(Argument::any())
            ->willReturn($this->template);

        $this->template->setBody(Argument::any())
            ->willReturn($this->template);

        $this->template->set(Argument::any(), Argument::any())
            ->willReturn($this->template);

        $this->template->setLocale(Argument::any())
            ->willReturn($this->template);

        $this->template->render()
            ->willReturn("Verify your email to get started");

        $this->proDomain->lookup('')
            ->willReturn(null);

        $this->confirmationUrl->setUser($user)
            ->willReturn($this->confirmationUrl);

        $this->confirmationUrl->generate(Argument::any())
            ->willReturn('confirmation-url');

        $message = $this->build();

        $html = $message->buildHtml();
        $html->shouldContain('Verify your email to get started');
    }

    // public function it_should_build_a_pro_specific_confirmation_email(User $user, Pro\Settings $proSettings)
    // {
    //     $user->getGUID()
    //         ->willReturn('123');
    //     $user->get('guid')
    //         ->willReturn('123');
    //     $user->get('username')
    //         ->willReturn('mark');
    //     $user->get('name')
    //         ->willReturn('mark');
    //     $user->getLanguage()
    //         ->willReturn('en');
    //     $user->getEmail()
    //         ->willReturn('mark@minds.com');
    //
    //     $this->setUser($user);
    //
    //     $this->template->setLocale(Argument::any())
    //         ->willReturn($this->template);
    //
    //     $this->template->getTranslator()
    //         ->willReturn(new Translator());
    //
    //     $this->proDomain->lookup('')
    //         ->willReturn($proSettings);
    //
    //     $proSettings->getTitle()
    //         ->willReturn('Pro Site');
    //
    //     $proSettings->getLogoImage()
    //         ->willReturn('this-is-a-pro-site-logo.jpg');
    //
    //     $proSettings->getDomain()
    //         ->willReturn('pro.site.com');
    //
    //     $this->confirmationUrl->setUser($user)
    //         ->willReturn($this->confirmationUrl);
    //
    //     $this->confirmationUrl->generate(Argument::any())
    //         ->willReturn('confirmation-url');
    //
    //     $message = $this->build($proSettings);
    //
    //     $html = $message->buildHtml();
    //     $html->shouldContain('Welcome to Pro Site');
    //
    //     $html->shouldContain('pro.site.com');
    //
    //     $html->shouldContain('this-is-a-pro-site-logo.jpg');
    // }
}
