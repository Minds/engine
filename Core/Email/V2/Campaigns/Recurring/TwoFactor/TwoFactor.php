<?php
/**
 * TwoFactor
 *
 * @author mark
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\TwoFactor;

use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Confirmation\Url as ConfirmationUrl;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;

class TwoFactor extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var string */
    protected $code;

    /**
     * Confirmation constructor.
     * @param Template $template
     * @param Mailer $mailer
     * @param ConfirmationUrl $confirmationUrl
     */
    public function __construct(
        $template = null,
        $mailer = null,
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();

        $this->campaign = 'global';
        $this->topic = 'confirmation';
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
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
            'state' => 'new',
        ];

        $this->template->setLocale($this->user->getLanguage());

        $translator = $this->template->getTranslator();

        $subject = $this->code . ' is your verification code for Minds';
        $previewText = 'Verify your email address with Minds';

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.v2.tpl');
        if ($this->user->isTrusted()) {
            $this->template->setBody('./template.v2.2fa.tpl');
            $previewText = "Verify your action with Minds";
        } else {
            $this->template->setBody('./template.v2.verify.tpl');
        }
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('preheader', $previewText);
        $this->template->set('title', $subject);

        $this->template->set('code', $this->code);

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
        if ($this->user && $this->user->getEmail()) {
            $this->mailer->send(
                $this->build(),
                true
            );

            $this->saveCampaignLog();
        }
    }
}
