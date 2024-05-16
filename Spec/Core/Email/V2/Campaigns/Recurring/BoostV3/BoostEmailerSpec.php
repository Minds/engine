<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\BoostV3;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Config\Config;
use Minds\Core\Email\Manager;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\BoostV3\BoostEmailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Utils\BoostConsoleUrlBuilder;
use Minds\Core\Boost\V3\Utils\BoostReceiptUrlBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BoostEmailerSpec extends ObjectBehavior
{
    private Collaborator $emailManager;
    private Collaborator $template;
    private Collaborator $mailer;
    private Collaborator $entitiesBuilder;
    private Collaborator $config;
    private Collaborator $consoleUrlBuilder;
    private Collaborator $receiptUrlBuilder;
    private Collaborator $logger;

    public function let(
        Manager $emailManager,
        Template $template,
        Mailer $mailer,
        EntitiesBuilder $entitiesBuilder,
        Config $config,
        BoostConsoleUrlBuilder $consoleUrlBuilder,
        BoostReceiptUrlBuilder $receiptUrlBuilder,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $emailManager,
            $template,
            $mailer,
            $entitiesBuilder,
            $config,
            $consoleUrlBuilder,
            $receiptUrlBuilder,
            $logger
        );
        $this->emailManager = $emailManager;
        $this->template = $template;
        $this->mailer = $mailer;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->config = $config;
        $this->consoleUrlBuilder = $consoleUrlBuilder;
        $this->receiptUrlBuilder = $receiptUrlBuilder;
        $this->logger = $logger;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(BoostEmailer::class);
    }

    public function it_should_build_a_boost_created_email_for_cash(
        Boost $boost,
        User $user
    ): void {
        $topic = ActionEvent::ACTION_BOOST_CREATED;
        $userGuid = '234';
        $userUsername = 'minds_receiver';
        $userEmail = 'no-reply@minds.com';
        $paymentMethod = BoostPaymentMethod::CASH;
        $paymentAmount = '100';
        $durationDays = 10;
        $campaign = 'when';
        $tracking = "__e_ct_guid=$userGuid&campaign=when&topic=$topic";
        $bodyText = "We're reviewing your Boost for \$$paymentAmount over $durationDays days. Once it's approved, your Boost will automatically begin running.";
        $headerText = "Your Boost is in review";
        $preHeaderText = "Here's what comes next.";
        $url = '~url~';
        $receiptUrl = '~receiptUrl~';

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $boost->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $boost->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $boost->getDurationDays()
            ->shouldBeCalled()
            ->willReturn($durationDays);

        $this->consoleUrlBuilder->build($boost, [
            '__e_ct_guid' => $userGuid,
            'campaign' => $campaign,
            'topic' => $topic,
        ])
            ->shouldBeCalled()
            ->willReturn($url);

        $this->receiptUrlBuilder->setBoost($boost)
            ->shouldBeCalled()
            ->willReturn($this->receiptUrlBuilder);

        $this->receiptUrlBuilder->build()
            ->shouldBeCalled()
            ->willReturn($receiptUrl);

        $this->template->setTemplate('default.v2.tpl')->shouldBeCalled();
        $this->template->setBody('./template.tpl')->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', 'when')
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $preHeaderText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('additionalCtaText', 'Receipt')
            ->shouldBeCalled();

        $this->template->set('additionalCtaPath', $receiptUrl)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($userUsername);


        $this->mailer->send(Argument::any())->shouldBeCalled();

        $this->emailManager->saveCampaignLog(Argument::that(function ($arg) {
            return is_numeric($arg->getTimeSent()) &&
                $arg->getEmailCampaignId() === "BoostEmailer";
        }))
            ->shouldBeCalled();

        $this->setBoost($boost)->setTopic($topic)->send();
    }

    public function it_should_build_a_boost_created_email_for_tokens(
        Boost $boost,
        User $user
    ): void {
        $topic = ActionEvent::ACTION_BOOST_CREATED;
        $userGuid = '234';
        $userUsername = 'minds_receiver';
        $userEmail = 'no-reply@minds.com';
        $paymentMethod = BoostPaymentMethod::OFFCHAIN_TOKENS;
        $paymentAmount = 100;
        $durationDays = 10;
        $campaign = 'when';
        $tracking = "__e_ct_guid=$userGuid&campaign=when&topic=$topic";
        $bodyText = "We're reviewing your Boost for $paymentAmount tokens over $durationDays days. Once it's approved, your Boost will automatically begin running.";
        $headerText = "Your Boost is in review";
        $preHeaderText = "Here's what comes next.";
        $url = '~url~';
        $receiptUrl = '~receiptUrl~';

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $boost->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $boost->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $boost->getDurationDays()
            ->shouldBeCalled()
            ->willReturn($durationDays);

        $this->consoleUrlBuilder->build($boost, [
            '__e_ct_guid' => $userGuid,
            'campaign' => $campaign,
            'topic' => $topic,
        ])
            ->shouldBeCalled()
            ->willReturn($url);

        $this->receiptUrlBuilder->setBoost($boost)
            ->shouldBeCalled()
            ->willReturn($this->receiptUrlBuilder);

        $this->receiptUrlBuilder->build()
            ->shouldBeCalled()
            ->willReturn($receiptUrl);

        $this->template->setTemplate('default.v2.tpl')->shouldBeCalled();
        $this->template->setBody('./template.tpl')->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', 'when')
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $preHeaderText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('additionalCtaText', 'Receipt')
            ->shouldBeCalled();

        $this->template->set('additionalCtaPath', $receiptUrl)
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($userUsername);


        $this->mailer->send(Argument::any())->shouldBeCalled();

        $this->emailManager->saveCampaignLog(Argument::that(function ($arg) {
            return is_numeric($arg->getTimeSent()) &&
                $arg->getEmailCampaignId() === "BoostEmailer";
        }))
            ->shouldBeCalled();

        $this->setBoost($boost)->setTopic($topic)->send();
    }

    public function it_should_build_a_boost_accepted_email(
        Boost $boost,
        User $user
    ): void {
        $topic = ActionEvent::ACTION_BOOST_ACCEPTED;
        $userGuid = '234';
        $userUsername = 'minds_receiver';
        $userEmail = 'no-reply@minds.com';
        $durationDays = 10;
        $campaign = 'when';
        $tracking = "__e_ct_guid=$userGuid&campaign=when&topic=$topic";
        $bodyText = "Your Boost has been approved and is actively running. The campaign will end in $durationDays day(s).";
        $headerText = "Your Boost is now running";
        $preHeaderText = "The Minds community is now seeing your Boost.";
        $url = '~url~';

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $boost->getDurationDays()
            ->shouldBeCalled()
            ->willReturn($durationDays);

        $this->consoleUrlBuilder->build($boost, [
            '__e_ct_guid' => $userGuid,
            'campaign' => $campaign,
            'topic' => $topic,
        ])
            ->shouldBeCalled()
            ->willReturn($url);

        $this->template->setTemplate('default.v2.tpl')->shouldBeCalled();
        $this->template->setBody('./template.tpl')->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', 'when')
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $preHeaderText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('additionalCtaText', '')
            ->shouldBeCalled();

        $this->template->set('additionalCtaPath', '')
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($userUsername);


        $this->mailer->send(Argument::any())->shouldBeCalled();

        $this->emailManager->saveCampaignLog(Argument::that(function ($arg) {
            return is_numeric($arg->getTimeSent()) &&
                $arg->getEmailCampaignId() === "BoostEmailer";
        }))
            ->shouldBeCalled();

        $this->setBoost($boost)->setTopic($topic)->send();
    }

    public function it_should_build_a_boost_completed_email(
        Boost $boost,
        User $user
    ): void {
        $topic = ActionEvent::ACTION_BOOST_COMPLETED;
        $userGuid = '234';
        $userUsername = 'minds_receiver';
        $userEmail = 'no-reply@minds.com';
        $paymentMethod = BoostPaymentMethod::CASH;
        $paymentAmount = 100;
        $durationDays = 10;
        $campaign = 'when';
        $tracking = "__e_ct_guid=$userGuid&campaign=when&topic=$topic";
        $bodyText = "Your Boost for \$$paymentAmount over $durationDays days is now complete. View the results and Boost again for even more reach and engagement.";
        $headerText = "Your Boost is complete";
        $preHeaderText = "View the results.";
        $url = '~url~';

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $boost->getPaymentMethod()
            ->shouldBeCalled()
            ->willReturn($paymentMethod);

        $boost->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn($paymentAmount);

        $boost->getDurationDays()
            ->shouldBeCalled()
            ->willReturn($durationDays);

        $this->consoleUrlBuilder->build($boost, [
            '__e_ct_guid' => $userGuid,
            'campaign' => $campaign,
            'topic' => $topic,
        ])
            ->shouldBeCalled()
            ->willReturn($url);

        $this->template->setTemplate('default.v2.tpl')->shouldBeCalled();
        $this->template->setBody('./template.tpl')->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', 'when')
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $preHeaderText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('additionalCtaText', '')
            ->shouldBeCalled();

        $this->template->set('additionalCtaPath', '')
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($userUsername);


        $this->mailer->send(Argument::any())->shouldBeCalled();

        $this->emailManager->saveCampaignLog(Argument::that(function ($arg) {
            return is_numeric($arg->getTimeSent()) &&
                $arg->getEmailCampaignId() === "BoostEmailer";
        }))
            ->shouldBeCalled();

        $this->setBoost($boost)->setTopic($topic)->send();
    }

    public function it_should_build_a_boost_rejected_from_safe_email(
        Boost $boost,
        User $user
    ): void {
        $topic = ActionEvent::ACTION_BOOST_REJECTED;

        $userGuid = '234';
        $entityGuid ='567';
        $userUsername = 'minds_receiver';
        $userEmail = 'no-reply@minds.com';
        $targetSuitability = BoostTargetSuitability::SAFE;
        $campaign = 'when';
        $tracking = "__e_ct_guid=$userGuid&campaign=when&topic=$topic";
        $bodyText = 'Your Boost has been reviewed, but was not approved. Please check the Boost console for more information. If your Boost was rejected for targeting the wrong audience, you can re-submit your Boost to another audience for approval.';
        $headerText = "Your Boost needs attention";
        $preHeaderText = "Please check the Boost console for more information.";
        $url = '~url~';

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $boost->getTargetSuitability()
            ->shouldBeCalled()
            ->willReturn($targetSuitability);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->consoleUrlBuilder->build($boost, [
            '__e_ct_guid' => $userGuid,
            'campaign' => $campaign,
            'topic' => $topic,
        ])
            ->shouldBeCalled()
            ->willReturn($url);

        $this->template->setTemplate('default.v2.tpl')->shouldBeCalled();
        $this->template->setBody('./template.tpl')->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', 'when')
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $preHeaderText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('additionalCtaText', '')
            ->shouldBeCalled();

        $this->template->set('additionalCtaPath', '')
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($userUsername);


        $this->mailer->send(Argument::any())->shouldBeCalled();

        $this->emailManager->saveCampaignLog(Argument::that(function ($arg) {
            return is_numeric($arg->getTimeSent()) &&
                $arg->getEmailCampaignId() === "BoostEmailer";
        }))
            ->shouldBeCalled();

        $this->setBoost($boost)->setTopic($topic)->send();
    }

    public function it_should_build_a_boost_rejected_from_controversial_email(
        Boost $boost,
        User $user
    ): void {
        $topic = ActionEvent::ACTION_BOOST_REJECTED;

        $userGuid = '234';
        $userUsername = 'minds_receiver';
        $userEmail = 'no-reply@minds.com';
        $targetSuitability = BoostTargetSuitability::CONTROVERSIAL;
        $campaign = 'when';
        $tracking = "__e_ct_guid=$userGuid&campaign=when&topic=$topic";
        $bodyText = 'We’ve reviewed your Boost and determined the content does not meet the content policy requirements for the selected audience. You have been refunded.';
        $headerText = "Your Boost was rejected";
        $preHeaderText = "Find out why.";

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $boost->getTargetSuitability()
            ->shouldBeCalled()
            ->willReturn($targetSuitability);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn($userEmail);

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $user->get('name')
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->consoleUrlBuilder->build($boost, [
            '__e_ct_guid' => $userGuid,
            'campaign' => $campaign,
            'topic' => $topic,
        ])
            ->shouldBeCalled()
            ->willReturn('~url~');

        $this->template->setTemplate('default.v2.tpl')->shouldBeCalled();
        $this->template->setBody('./template.tpl')->shouldBeCalled();

        $this->template->set('user', $user)
            ->shouldBeCalled();

        $this->template->set('username', $userUsername)
            ->shouldBeCalled();

        $this->template->set('email', $userEmail)
            ->shouldBeCalled();

        $this->template->set('guid', $userGuid)
            ->shouldBeCalled();

        $this->template->set('campaign', 'when')
            ->shouldBeCalled();

        $this->template->set('topic', $topic)
            ->shouldBeCalled();

        $this->template->set('tracking', $tracking)
            ->shouldBeCalled();

        $this->template->set('title', '')
            ->shouldBeCalled();

        $this->template->set('state', '')
            ->shouldBeCalled();

        $this->template->set('preheader', $preHeaderText)
            ->shouldBeCalled();

        $this->template->set('bodyText', $bodyText)
            ->shouldBeCalled();

        $this->template->set('headerText', $headerText)
            ->shouldBeCalled();

        $this->template->set('additionalCtaText', '')
            ->shouldBeCalled();

        $this->template->set('additionalCtaPath', '')
            ->shouldBeCalled();

        $this->template->set('actionButton', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($userUsername);

        $this->mailer->send(Argument::any())->shouldBeCalled();

        $this->emailManager->saveCampaignLog(Argument::that(function ($arg) {
            return is_numeric($arg->getTimeSent()) &&
                $arg->getEmailCampaignId() === "BoostEmailer";
        }))
            ->shouldBeCalled();

        $this->setBoost($boost)->setTopic($topic)->send();
    }
}
