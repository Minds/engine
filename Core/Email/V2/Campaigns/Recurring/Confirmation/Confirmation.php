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
use Minds\Core\Email\V2\Partials\ProHeader\ProHeader;
use Minds\Core\Pro;

class Confirmation extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var ConfirmationUrl */
    protected $confirmationUrl;

    /** @var Pro\Domain */
    protected $proDomain;

    /**
     * Confirmation constructor.
     * @param Template $template
     * @param Mailer $mailer
     * @param ConfirmationUrl $confirmationUrl
     */
    public function __construct(
        $template = null,
        $mailer = null,
        $confirmationUrl = null,
        $proDomain = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->confirmationUrl = $confirmationUrl ?: Di::_()->get('Email\Confirmation\Url');
        $this->proDomain = $proDomain ?: Di::_()->get('Pro\Domain');

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

        $this->template->setLocale($this->user->getLanguage());

        $translator = $this->template->getTranslator();

        $subject = $translator->trans('Welcome to Minds. Time to verify.');

        /** @var Pro\Settings */
        $proSettings = $this->proDomain->lookup($_SERVER['HTTP_HOST'] ?? '');

        if ($proSettings) {
            $subject = 'Welcome to ' . $proSettings->getTitle() . '. Time to verify.';
        }

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('title', $proSettings ? 'Welcome to ' . $proSettings->getTitle() : $translator->trans('Welcome to Minds'));
        $this->template->set('preheader', $subject);
        $this->template->set('isPro', !!$proSettings);

        if ($proSettings) {
            $proHeader = (new ProHeader())
                ->set('tracking', $trackingQuery)
                ->setProSettings($proSettings);
            $this->template->set('custom_header', $proHeader->build());
        }

        $actionButton = (new ActionButton())
            ->setLabel($translator->trans('Verify Address'))
            ->setPath(
                $this->confirmationUrl
                    ->setUser($this->user)
                    ->generate($tracking)
            );

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
