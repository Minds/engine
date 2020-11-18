<?php
/**
 * OnPlusTrial
 *
 * @author Mark
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\OnPlusTrial;

use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Confirmation\Url as ConfirmationUrl;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Payments\Subscriptions\Subscription;

class OnPlusTrial extends EmailCampaign
{
    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var Subscription */
    protected $subscription;

    /**
     * Confirmation constructor.
     * @param Template $template
     * @param Mailer $mailer
     * @param ConfirmationUrl $confirmationUrl
     */
    public function __construct(
        $template = null,
        $mailer = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();

        $this->campaign = 'global';
        $this->topic = 'plus';
    }

    /**
     * Set the subscription we are dealing with
     * !! Trials have subscriptions. The next billing date is the end of trial period !!
     * @param Subscription $subscription
     * @return OnPlusTrial
     */
    public function setSubscription(Subscription $subscription): OnPlusTrial
    {
        $onPlusTrial = clone $this;
        $onPlusTrial->subscription = $subscription;
        return $onPlusTrial;
    }

    /**
     * @return Message
     */
    public function build()
    {
        if (!$this->subscription) {
            throw new \Exception("Subscription must be provided");
        }

        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => $this->campaign,
            'topic' => $this->topic,
            'utm_campaign' => 'plus_trial_welcome',
            'utm_medium' => 'email',
        ];

        $this->template->setLocale($this->user->getLanguage());

        $translator = $this->template->getTranslator();

        $subject = $translator->trans('Welcome to Minds+');

        $trackingQuery = http_build_query($tracking);

        $this->template->setTemplate('default.tpl');
        $this->template->setBody('./template.tpl');
        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('preheader', $subject);
        $this->template->set('next_payment_formatted', date('jS F Y', $this->subscription->getNextBilling()));

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

            $this->mailer->send(
                $this->build(),
                true
            );

            $this->saveCampaignLog();
        }
    }
}
