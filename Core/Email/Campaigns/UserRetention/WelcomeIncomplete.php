<?php

namespace Minds\Core\Email\Campaigns\UserRetention;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Template;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Message;
use Minds\Core\Email\Manager;
use Minds\Traits\MagicAttributes;
use Minds\Core\Email\Partials\SuggestedChannels;
use Minds\Core\Di\Di;

class WelcomeIncomplete extends EmailCampaign
{
    use MagicAttributes;
    protected $db;
    protected $template;
    protected $mailer;
    protected $amount;
    protected $campaign;
    protected $suggestions;
    protected $config;

    public function __construct(Template $template = null, Mailer $mailer = null, Manager $manager = null)
    {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');

        $this->campaign = 'global';
        $this->topic = 'minds_tips';
        $this->state = 'new';
    }

    public function build()
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'state' => $this->state,
        ];

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./Templates/welcome_incomplete.tpl');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('state', $this->state);
        $this->template->set('tracking', http_build_query($tracking));
        $suggestedChannels = (new SuggestedChannels())
            ->setTracking(http_build_query($tracking))
            ->setSuggestions($this->suggestions);

        $this->template->set('suggestions', $suggestedChannels->build());

        $subject = 'Welcome to Minds';

        $message = new Message();
        $message->setTo($this->user)
            ->setMessageId(implode('-',
                [$this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign.$this->topic.time())]))
            ->setSubject($subject)
            ->setHtml($this->template);

        return $message;
    }

    public function send()
    {
        //send email
        if ($this->canSend()) {
            $this->mailer->queue($this->build());
        }
    }
}
