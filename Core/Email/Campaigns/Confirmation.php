<?php
/**
 * Confirmation
 *
 * @author edgebal
 */

namespace Minds\Core\Email\Campaigns;

use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Url as ConfirmationUrl;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Message;
use Minds\Core\Email\Template;

class Confirmation extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var ConfirmationUrl */
    protected $confirmationUrl;

    /**
     * Confirmation constructor.
     * @param Template $template
     * @param Mailer $mailer
     * @param ConfirmationUrl $confirmationUrl
     */
    public function __construct(
        $template = null,
        $mailer = null,
        $confirmationUrl = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->confirmationUrl = $confirmationUrl ?: Di::_()->get('Email\Confirmation\Url');

        $this->campaign = 'global';
        $this->topic = 'confirmation';
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

        $subject = 'Please, confirm your email';

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./Templates/confirmation.tpl');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', http_build_query($tracking));
        $this->template->set(
            'confirmation_url',
            $this->confirmationUrl
                ->setUser($this->user)
                ->generate($tracking)
        );

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
            // User is still not enabled

            $this->mailer->queue(
                $this->build(),
                true
            );

            $this->saveCampaignLog();
        }
    }
}
