<?php

/**
 * Custom Campaign Emails
 */

namespace Minds\Core\Email\V2\Campaigns\Custom;

use Minds\Core\Config;
use Minds\Core\Entities;
use Minds\Core\Data\Call;
use Minds\Core\Analytics\Timestamps;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;

class Custom
{
    // TODO code docs
    protected $db;
    protected $template;
    protected $mailer;

    protected $user;
    protected $subject = "";
    protected $templateKey = "";
    protected $topic = "";
    protected $campaign = "";
    protected $title = "";
    protected $signoff = "";
    protected $preheader = "";
    protected $hideDownloadLinks = false;

    /** @var Message */
    public $message;


    public function __construct(Call $db = null, Template $template = null, Mailer $mailer = null)
    {
        $this->db = $db ?: new Call('entities_by_time');
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setTemplate($template)
    {
        $this->templateKey = $template;
        return $this;
    }

    public function setTopic($topic)
    {
        $this->topic = $topic;
        return $this;
    }

    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setSignoff($signoff)
    {
        $this->signoff = $signoff;
        return $this;
    }

    public function setPreheader($preheader)
    {
        $this->preheader = $preheader;
        return $this;
    }

    public function setHideDownloadLinks($hideDownloadLinks)
    {
        $this->hideDownloadLinks = $hideDownloadLinks;
        return $this;
    }


    public function setVars($vars)
    {
        $this->vars = $vars;
        return $this;
    }

    public function send()
    {
        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
        ];
        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.tpl');
        $this->template->setBody("./Templates/$this->templateKey.tpl");
        $this->template->toggleMarkdown(true);
        $this->template->setLocale($this->user->getLanguage());

        $validatorHash = sha1($this->campaign . $this->user->guid . Config::_()->get('emails_secret'));

        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('user', $this->user);
        $this->template->set('topic', $this->topic);

        $this->template->set('preheader', $this->preheader);
        $this->template->set('title', $this->title);
        $this->template->set('signoff', $this->signoff);
        $this->template->set('hideDownloadLinks', $this->hideDownloadLinks);

        $this->template->set('campaign', $this->campaign);
        $this->template->set('validator', $validatorHash);
        $this->template->set('tracking', $trackingQuery);

        foreach ($this->vars as $key => $var) {
            $this->template->set($key, $var);
        }

        $this->message = new Message();
        $this->message->setTo($this->user)
            ->setMessageId(implode('-', [$this->user->guid, sha1($this->user->getEmail()), $validatorHash]))
            ->setSubject($this->subject)
            ->setHtml($this->template);

        //send email
        $this->mailer->send($this->message);
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}
