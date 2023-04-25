<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\BoostPolicyViolationEmailer;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Email\Manager;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Boost\V3\Utils\BoostConsoleUrlBuilder;
use Minds\Core\Email\V2\Campaigns\Recurring\BoostPolicyViolationEmailer\BoostPolicyViolationEmailer;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BoostPolicyViolationEmailerSpec extends ObjectBehavior
{
    private Collaborator $emailManager;
    private Collaborator $template;
    private Collaborator $mailer;
    private Collaborator $consoleUrlBuilder;

    public function let(
        Manager $emailManager,
        Template $template,
        Mailer $mailer,
        BoostConsoleUrlBuilder $consoleUrlBuilder
    ) {
        $this->beConstructedWith(
            $emailManager,
            $template,
            $mailer,
            $consoleUrlBuilder,
        );
        $this->emailManager = $emailManager;
        $this->template = $template;
        $this->mailer = $mailer;
        $this->consoleUrlBuilder = $consoleUrlBuilder;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(BoostPolicyViolationEmailer::class);
    }

    public function it_should_send_an_email(Activity $entity, User $user): void
    {
        $userGuid = '123';
        $url = '~url~';
        $username = 'username';
        $email = 'noreply@minds.com';
        $name = 'name';

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($name);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($username);
 
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($email);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->consoleUrlBuilder->buildWithFilters(
            BoostStatus::REJECTED,
            BoostTargetLocation::NEWSFEED,
            [
                '__e_ct_guid' => $userGuid,
                'campaign' => 'when',
                'topic' => 'boost_policy_violation',
            ]
        )
            ->shouldBeCalled()
            ->willReturn($url);

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $username)
           ->shouldBeCalled();

        $this->template->set('email', $email)
          ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
          ->shouldBeCalled();

        $this->template->set('campaign', 'when')
          ->shouldBeCalled();

        $this->template->set('topic', 'boost_policy_violation')
          ->shouldBeCalled();

        $this->template->set('tracking', http_build_query([
            '__e_ct_guid' => $userGuid,
            'campaign' => 'when',
            'topic' => 'boost_policy_violation',
        ]))
           ->shouldBeCalled();

        $this->template->set('title', '')
          ->shouldBeCalled();

        $this->template->set('state', '')
           ->shouldBeCalled();

        $this->template->set('preheader', 'Your Boost has been canceled')
           ->shouldBeCalled();

        $this->template->set('bodyText', 'Your in-progress Boost has been canceled due to issues with the <a href="https://support.minds.com/hc/en-us/articles/11723536774292-Boost-Content-Policy" target="_blank">Boost content policy</a>.')
          ->shouldBeCalled();

        $this->template->set('headerText', 'Unfortunately your Boost has been canceled')
          ->shouldBeCalled();
        
        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->mailer->send(Argument::any())
            ->shouldBeCalled();

        $this->setEntity($entity)
            ->setUser($user)
            ->send();
    }

    public function it_should_queue_an_email(Activity $entity, User $user): void
    {
        $userGuid = '123';
        $url = '~url~';
        $username = 'username';
        $email = 'noreply@minds.com';
        $name = 'name';

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($name);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($username);
 
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($email);

        $this->template->setTemplate('default.v2.tpl')
            ->shouldBeCalled();

        $this->template->setBody('./template.tpl')
            ->shouldBeCalled();

        $this->consoleUrlBuilder->buildWithFilters(
            BoostStatus::REJECTED,
            BoostTargetLocation::NEWSFEED,
            [
                '__e_ct_guid' => $userGuid,
                'campaign' => 'when',
                'topic' => 'boost_policy_violation',
            ]
        )
            ->shouldBeCalled()
            ->willReturn($url);

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $username)
           ->shouldBeCalled();

        $this->template->set('email', $email)
          ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
          ->shouldBeCalled();

        $this->template->set('campaign', 'when')
          ->shouldBeCalled();

        $this->template->set('topic', 'boost_policy_violation')
          ->shouldBeCalled();

        $this->template->set('tracking', http_build_query([
            '__e_ct_guid' => $userGuid,
            'campaign' => 'when',
            'topic' => 'boost_policy_violation',
        ]))
           ->shouldBeCalled();

        $this->template->set('title', '')
          ->shouldBeCalled();

        $this->template->set('state', '')
           ->shouldBeCalled();

        $this->template->set('preheader', 'Your Boost has been canceled')
           ->shouldBeCalled();

        $this->template->set('bodyText', 'Your in-progress Boost has been canceled due to issues with the <a href="https://support.minds.com/hc/en-us/articles/11723536774292-Boost-Content-Policy" target="_blank">Boost content policy</a>.')
          ->shouldBeCalled();

        $this->template->set('headerText', 'Unfortunately your Boost has been canceled')
          ->shouldBeCalled();
        
        $this->template->set('actionButton', Argument::any())
            ->shouldBeCalled();

        $this->mailer->queue(Argument::any())
            ->shouldBeCalled();

        $this->setEntity($entity)
            ->setUser($user)
            ->queue();
    }
}
