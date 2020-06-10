<?php

namespace Spec\Minds\Core\Email\V2\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Email\V2\Campaigns\Recurring\WelcomeComplete\WelcomeComplete;
use Minds\Core\Email\V2\Campaigns\Recurring\WelcomeIncomplete\WelcomeIncomplete;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Delegates\WelcomeSender;
use Minds\Core\I18n\Translator;
use Minds\Core\Onboarding\Manager as OnboardingManager;
use Minds\Core\Suggestions\Manager as SuggestionsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class WelcomeSenderSpec extends ObjectBehavior
{
    /** @var SuggestionsManager */
    private $suggestionsManager;
    /** @var OnboardingManager */
    private $onboardingManager;
    /** @var WelcomeComplete */
    private $welcomeComplete;
    /** @var WelcomeIncomplete */
    private $welcomeIncomplete;

    public function let(
        Manager $manager,
        SuggestionsManager $suggestionsManager,
        OnboardingManager $onboardingManager,
        Mailer $mailer,
        Template $template,
        Translator $translator,
        WelcomeComplete $welcomeComplete,
        WelcomeIncomplete $welcomeIncomplete
    ) {
        $template->getTranslator()
            ->willReturn($translator);

        $template->setLocale(Argument::any())
            ->willReturn($translator);

        $translator->trans(Argument::any())
            ->willReturn('');

        Di::_()->bind('I18n\Translator', function () use ($translator) {
            return $translator->getWrappedObject();
        });

        $this->suggestionsManager = $suggestionsManager;
        $this->onboardingManager = $onboardingManager;
        $this->welcomeComplete = $welcomeComplete;
        $this->welcomeIncomplete = $welcomeIncomplete;
        $this->beConstructedWith($suggestionsManager, $onboardingManager, $welcomeComplete, $welcomeIncomplete);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(WelcomeSender::class);
    }

    public function it_should_send_a_welcome_complete(User $user)
    {
        $this->onboardingManager->setUser($user)->shouldBeCalled();
        $this->onboardingManager->isComplete()->shouldBeCalled()->willReturn(true);
        $this->suggestionsManager->setUser($user)->shouldBeCalled();
        $this->suggestionsManager->getList()->shouldBeCalled();

        $this->send($user);
    }

    public function it_should_send_a_welcome_incomplete(User $user)
    {
        $this->onboardingManager->setUser($user)->shouldBeCalled();
        $this->onboardingManager->isComplete()->shouldBeCalled()->willReturn(false);
        $this->suggestionsManager->setUser($user)->shouldNotBeCalled();
        $this->suggestionsManager->getList()->shouldNotBeCalled();

        $this->send($user);
    }
}
