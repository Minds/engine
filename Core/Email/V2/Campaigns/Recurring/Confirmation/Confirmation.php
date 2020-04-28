<?php
/**
 * Confirmation
 *
 * @author edgebal
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\Confirmation;

use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Confirmation\Url as ConfirmationUrl;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Partials\ActionButton\ActionButton;

class Confirmation extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var ConfirmationUrl */
    protected $confirmationUrl;

    /** @var ActionButton */
    protected $actionButton;

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

        $subject = 'Welcome to Minds. Time to verify.';
        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('title', 'Welcome to Minds');
        $this->template->set('preheader', $subject);

        $actionButton = (new ActionButton())
        ->setLabel('Verify Address')
        ->setPath($this->confirmationUrl
            ->setUser($this->user)
            ->generate($tracking));

        $this->template->set('actionButton', $actionButton->build());

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
