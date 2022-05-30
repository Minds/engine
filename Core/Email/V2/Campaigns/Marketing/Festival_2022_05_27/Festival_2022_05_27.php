<?php

namespace Minds\Core\Email\V2\Campaigns\Marketing\Festival_2022_05_27;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Manager;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;

class Festival_2022_05_27 extends EmailCampaign
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
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_medium' => 'email',
            'utm_campaign' => 'change-2022-03-15',
            'utm_source' => 'manual',
        ];

        $subject = "Tickets Live For Minds Festival Feat. Timcast, Cornel West and More...";

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
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', "We are excited to announce that we will be hosting our first Minds Festival of Ideas at The Beacon Theatre in NYC on June 25, 2022, and we wanted to formally invite you to be a part of this historic event.");

        $trackingQuery = http_build_query($tracking);
        $this->template->set('tracking', $trackingQuery);

        $actionButton = (new ActionButton())
            ->setLabel("Buy Tickets Now")
            ->setPath(
                "https://www.ticketmaster.com/event/3B005CB2CF161F8D"
            );

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
        if ($this->canSend()) {
            $this->mailer->send($this->build());
        }
    }
}
