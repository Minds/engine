<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\WeMissYou;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Traits\MagicAttributes;
use Minds\Core\Email\V2\Partials\SuggestedChannels\SuggestedChannels;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Di\Di;

class WeMissYou extends EmailCampaign
{
    use MagicAttributes;
    protected $db;
    protected $template;
    protected $mailer;
    protected $amount;
    protected $campaign;
    protected $suggestions;
    protected $config;

    /** @var ActionButton */
    protected $actionButton;

    public function __construct(Template $template = null, Mailer $mailer = null, Manager $manager = null)
    {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');

        $this->campaign = 'global';
        $this->topic = 'minds_tips';
        $this->state = 'cold';
    }

    public function build()
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'state' => $this->state,
        ];

        $trackingQuery = http_build_query($tracking);
        $subject = 'We Miss You';

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('title', $subject);
        $this->template->set('signoff', 'We hope to see you again!');
        $this->template->set('preheader', 'We want you back!');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('state', $this->state);
        $this->template->set('tracking', $trackingQuery);

        $actionButton = (new ActionButton())
        ->setPath('newsfeed/subscribed?'. $trackingQuery)
        ->setLabel('Check-in');

        $this->template->set('actionButton', $actionButton->build());

        $suggestedChannels = (new SuggestedChannels())
            ->setTracking(http_build_query($tracking))
            ->setSuggestions($this->suggestions);

        $this->template->set('suggestions', $suggestedChannels->build());



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

    public function send($time = null)
    {
        $time = $time ?: time();
        //send email
        if ($this->canSend()) {
            $this->mailer->queue($this->build());
            $this->saveCampaignLog($time);
        }
    }
}
