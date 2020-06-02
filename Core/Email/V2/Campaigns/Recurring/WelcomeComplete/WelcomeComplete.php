<?php

namespace Minds\Core\Email\V2\Campaigns\Recurring\WelcomeComplete;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Partials\SuggestedChannels\SuggestedChannels;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;

/**
 * Class WelcomeComplete
 * @package Minds\Core\Email\V2\Campaigns\Recurring\WelcomeComplete
 * @method WelcomeComplete setSuggestions(array $value)
 */
class WelcomeComplete extends EmailCampaign
{
    use MagicAttributes;

    /** @var  \Minds\Core\Email\V2\Common\Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var string */
    protected $campaign;

    /** @var array */
    protected $suggestions;

    /** @var ActionButton */
    protected $actionButton;


    public function __construct(Template $template = null, Mailer $mailer = null, Manager $manager = null)
    {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');

        $this->campaign = 'global';
        $this->topic = 'minds_tips';
        $this->state = 'new';
    }

    /**
     * Build template
     * @return Message
     */

    public function build(): Message
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'state' => $this->state,
        ];
        $trackingQuery = http_build_query($tracking);
        $this->template->setLocale($this->user->getLanguage());
        $translator = $this->template->getTranslator();
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
        $this->template->set('preheader', $translator->trans("Here's a free token for your new channel."));
        $this->template->set('tracking', $trackingQuery);

        $actionButton = (new ActionButton())
        ->setPath('newsfeed/subscribed?'. $trackingQuery)
        ->setLabel($translator->trans('Make a Post'));

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

    /** Send the email
     * @return void
     */
    public function send($time = null): void
    {
        $time = $time ?: time();
        //send email
        if ($this->canSend()) {
            $this->mailer->queue($this->build());
            $this->saveCampaignLog($time);
        }
    }
}
