<?php

namespace Minds\Core\Email\V2\Campaigns\Marketing\Plus2020_10_28;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Manager;
use Minds\Core\Wire\Wire;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;
use Minds\Core\Queue\Client as QueueClient;

class Plus2020_10_28 extends EmailCampaign
{
    use MagicAttributes;
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Manager */
    protected $manager;

    public function __construct(Template $template = null, Mailer $mailer = null, Manager $manager = null)
    {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->manager = $manager ?: Di::_()->get('Email\Manager');

        $this->campaign = 'global';
        $this->topic = 'minds_news';
    }

    public function build(): Message
    {
        $validatorHash = sha1(get_class($this) . $this->user->getGUID() . Di::_()->get('Config')->get('emails_secret'));
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'validator' => $validatorHash,
            'unixts' => time(),
            'utm_campaign' => 'plus2020_10_28',
            'utm_medium' => 'email',
        ];

        $subject = "You have a free trial to Minds+";

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->toggleMarkdown(true);
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGUID());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        // $this->template->set('signoff', '');
        $this->template->set('preheader', "");
        $this->template->set('tracking', http_build_query($tracking));
        
        // Send a push
        $title = "Start your free trial today";
        $message = "Minds+ now has revenue-sharing. Start your free trial today.💡";
        QueueClient::build()
                ->setQueue('Push')
                ->send([
                'user_guid' => $this->user->getGuid(),
                'uri' => 'notification',
                'title' => $title,
                'message' => $message,
            ]);
   
        $actionButton = (new ActionButton())
            ->setLabel('Start your free trial today')
            ->setPath('plus?'.http_build_query($tracking));

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

    public function send(): void
    {
        if ($this->user->isPlus()) {
            return;
        }
        if ($this->canSend()) {
            $this->mailer->send($this->build());
        }
    }
}
