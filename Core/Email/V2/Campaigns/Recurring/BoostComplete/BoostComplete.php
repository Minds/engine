<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\BoostComplete;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Email\Manager;
use Minds\Core\Di\Di;

class BoostComplete extends EmailCampaign
{
    // TODO code docs
    protected $db;
    protected $template;
    protected $mailer;

    protected $boost;

    /** @var ActionButton */
    protected $actionButton;

    public function __construct(Template $template = null, Mailer $mailer = null, Manager $manager = null)
    {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->campaign = 'when';
        $this->topic = 'boost_completed';
        $this->manager = $manager ?: Di::_()->get('Email\Manager');
    }

    public function setBoost($boost)
    {
        $this->boost = $boost;

        return $this;
    }

    public function build()
    {
        if (!$this->user) {
            return false;
        }

        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
        ];
        $trackingQuery = http_build_query($tracking);

        $subject = 'Boost Completed';
        $this->template->set('title', $subject);
        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');

        $this->template->set('guid', $this->user->guid);
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('boost', $this->boost);
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('preheader', 'Your boost is complete.');
        $this->template->set('signoff', 'Thank you,');
        $this->template->set('tracking', $trackingQuery);


        $actionButton = (new ActionButton())
        ->setPath('boost/console?'. $trackingQuery)
        ->setLabel('View Boost');
        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [$this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign.$this->topic.time())]
            ))
            ->setSubject($subject)
            ->setHtml($this->template);

        return $message;
    }

    public function send()
    {
        if ($this->canSend()) {
            $this->mailer->queue($this->build());
        }
    }
}
