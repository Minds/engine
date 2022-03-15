<?php
/**
 * Onboard to rewards
 *
 * @author mark
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\OnboardToRewards;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Experiments;
use Minds\Core\Di\Di;

class OnboardToRewards extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Experiments\Manager */
    protected $experimentsManager;

    /**
     * @param Template $template
     * @param Mailer $mailer
     * @param Experiments\Manager $experimentsManager
     * @param Email\Manager $emailManager
     */
    public function __construct(
        $template = null,
        $mailer = null,
        $experimentsManager = null,
        $emailManager = null
    ) {
        parent::__construct($emailManager);

        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->experimentsManager = $experimentsManager ?? Di::_()->get('Experiments\Manager');

        $this->campaign = 'with';
        $this->topic = 'channel_improvement_tips';
    }

    /**
     * @return Message
     */
    public function build()
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_campaign' => 'onboard_to_rewards',
            'utm_medium' => 'email',
            'utm_source' => 'signups', // TODO: too generic. use SendList id?
        ];

        $this->template->setLocale($this->user->getLanguage());

        $subject = "Get paid for your posts on Minds";

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('title', "");
        $this->template->set('preheader', "Join Minds Rewards to claim your share of daily crypto payouts.");

        $actionButton = (new ActionButton())
            ->setLabel("Join Rewards")
            ->setPath(
                "https://www.minds.com/wallet/tokens/rewards?$trackingQuery&utm_content=cta_button"
            );

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
     * @return void
     */
    public function send()
    {
        if (!$this->canSend()) {
            return;
        }

        if (!$this->user->isEmailConfirmed()) {
            return;
        }

        if ($this->user->getPhoneNumberHash()) {
            return; // They are already in rewards
        }

        if (!$this->experimentsManager->setUser($this->user)->isOn('minds-2958-email')) {
            return; // Not in experiment
        }

        $this->mailer->send(
            $this->build(),
            true
        );

        $this->saveCampaignLog();
    }
}
