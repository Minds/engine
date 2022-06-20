<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\PostSignupSurvey;

use Minds\Core\Config\Config;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\Email\Manager as EmailManager;
use Minds\Core\Log\Logger;

/**
 * Post signup survey email, sends users a survey links asking
 * for their opinions on the site.
 */
class PostSignupSurvey extends EmailCampaign
{
    /**
     * Constructor.
     * @param ?Template $template
     * @param ?Mailer $mailer
     * @param ?ExperimentsManager $experimentsManager
     * @param ?EmailManager $emailManager
     * @param ?Logger $logger
     * @param ?Config $config
     */
    public function __construct(
        private ?Template $template = null,
        private ?Mailer $mailer = null,
        private ?ExperimentsManager $experimentsManager = null,
        private ?EmailManager $emailManager = null,
        private ?Logger $logger = null,
        private ?Config $config = null
    ) {
        parent::__construct($emailManager);

        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->experimentsManager = $experimentsManager ?? Di::_()->get('Experiments\Manager');
        $this->logger ??= Di::_()->get('Logger');
        $this->config ??= Di::_()->get('Config');

        $this->campaign = 'with';
        $this->topic = 'channel_improvement_tips';
    }

    /**
     * Build the email.
     * @return Message - message.
     */
    public function build(): ?Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_campaign' => 'welcome',
            'utm_medium' => 'email',
            'utm_source' => 'signups'
        ];

        $surveyLink = $this->config->get('survey_links')['post_signup'] ?? false;

        if (!$surveyLink) {
            $this->logger->warn('PostSignupSurvey survey link not set in config.');
            return null;
        }

        $this->template->setLocale($this->user->getLanguage());

        $translator = $this->template->getTranslator();

        $subject = $translator->trans('What do you think of Minds?');
        $previewText = $translator->trans("Positive or negative, we'd love your feedback.͏‌");

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./body-template.tpl');

        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', $previewText);

        $actionButton = (new ActionButtonV2())
            ->setLabel($translator->trans('Quick Survey'))
            ->setPath($surveyLink);

        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message
            ->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [ $this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign . $this->topic . time()) ]
            ))
            ->setSubject($subject)
            ->setHtml($this->template);

        return $message;
    }

    /**
     * Send the email.
     * @return void
     */
    public function send(): void
    {
        if (!$this->canSend()) {
            return;
        }

        if (!$this->user->isEmailConfirmed()) {
            return;
        }

        // Feature off.
        if (!$this->experimentsManager->setUser($this->user)->isOn('minds-3132-post-signups')) {
            $this->logger->warn('PostSignupSurvey email not sent as experiment is off.');
            return;
        }

        $this->mailer->send(
            $this->build(),
            true
        );

        $this->saveCampaignLog();
    }
}
