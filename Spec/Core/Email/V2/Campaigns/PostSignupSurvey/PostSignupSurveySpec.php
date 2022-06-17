<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\Confirmation;

use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\Confirmation\Confirmation;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Manager as EmailManager;
use Minds\Core\Log\Logger;
use Minds\Core\Config\Config;
use Minds\Core\I18n\Translator;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConfirmationSpec extends ObjectBehavior
{
    /** @var Template */
    protected $template;
    
    /** @var Mailer */
    protected $mailer;
    
    /** @var  ExperimentsManager */
    protected $experimentsManager;
    
    /** @var EmailManager */
    protected $emailManager;
    
    /** @var Logger */
    protected $logger;
    
    /** @var Config*/
    protected $config;

    public function let(
        Template $template,
        Mailer $mailer,
        ExperimentsManager $experimentsManager,
        EmailManager $emailManager,
        Logger $logger,
        Config $config
    ) {
        $this->beConstructedWith(
            $template,
            $mailer,
            $experimentsManager,
            $emailManager,
            $logger,
            $config
        );

        $this->template = $template;
        $this->mailer = $mailer;
        $this->experimentsManager = $experimentsManager;
        $this->emailManager = $emailManager;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Confirmation::class);
    }

    public function it_should_build_a_normal_confirmation_email(User $user)
    {
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

        $this->config->get('survey_links')
            ->shouldBeCalled()
            ->willReturn([
                'post_signup' => 'https://survey-link',
            ]);
    
        $this->template->setLocale('en')
            ->willReturn($this->template);

        $this->template->getTranslator()
            ->willReturn(new Translator());

        $message = $this->build();

        $html = $message->buildHtml();
        $html->shouldContain('Verify your email to get started');
    }
}
