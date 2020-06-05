<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\WelcomeIncomplete;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Partials\SuggestedChannels\SuggestedChannels;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Traits\MagicAttributes;
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
    protected $actionButton;
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

        $this->template->setLocale($this->user->getLanguage());

        $translator = $this->template->getTranslator();

        $trackingQuery = http_build_query($tracking);
        $subject = $translator->trans('Welcome to Minds');

        $this->template->set('title', $subject);
        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('state', $this->state);
        $this->template->set('preheader', $translator->trans('Enjoy all of the different features Minds has to offer when you finish setting up your channel'));
        $this->template->set('tracking', $trackingQuery);

        $actionButton = (new ActionButton())
        ->setPath('newsfeed/subscribed?'. $trackingQuery)
        ->setLabel($translator->trans('Complete Setup'));

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

    public function send()
    {
        //send email
        if ($this->canSend()) {
            $this->mailer->queue($this->build());
            $this->saveCampaignLog();
        }
    }
}
