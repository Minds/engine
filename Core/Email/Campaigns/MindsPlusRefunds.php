<?php
/**
 * Custom Campaign Emails
 */

namespace Minds\Core\Email\Campaigns;

use Minds\Core\Config;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Message;
use Minds\Core\Email\Template;

/**
 * Mail campaign to address Minds+ refunds
 */
class MindsPlusRefunds extends EmailCampaign
{
    protected Template $template;
    protected Mailer $mailer;

    protected string $subject = "";
    protected string $templateKey = "";

    public function __construct(Template $template = null, Mailer $mailer = null)
    {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->campaign = 'global';
        $this->topic = 'minds_plus_refund';
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function setTemplateKey($key): self
    {
        $this->templateKey = $key;
        return $this;
    }

    /**
     * @return Message
     */
    public function send(): Message
    {
        $this->template->setTemplate('default.tpl');
        $this->template->setBody("./Templates/$this->templateKey.tpl");

        $validatorHash = sha1($this->campaign . $this->topic . $this->user->guid . Config::_()->get('emails_secret'));

        $this->template->set('username', $this->user->username);

        $message = new Message();
        $message->setTo($this->user)
            ->setMessageId(implode('-', [$this->user->guid, sha1($this->user->getEmail()), $validatorHash]))
            ->setSubject($this->subject)
            ->setHtml($this->template);
        $this->mailer->queue($message);

        return $message;
    }
}
