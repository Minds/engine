<?php
/**
 * Supermind emailer
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\Supermind;

use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Traits\MagicAttributes;
use Minds\Entities\User;

class Supermind extends EmailCampaign
{
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var SupermindRequest */
    protected $supermindRequest;

    /** @var User */
    protected $emailRecipient;

    /** @var string */
    protected $topic;

    /**
     * Constructor.
     * @param Template $template
     * @param Mailer $mailer
     */
    public function __construct(
        $template = null,
        $mailer = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
    }

    /**
     * @return Message
     */
    public function build()
    {
        if (!$this->topic) {
            return;
        }

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');

        /** @var String */
        $paymentString = $this->buildPaymentString($this->supermindRequest);

        /** @var User */
        $requester = $this->buildUser($this->supermindRequest->getSenderGuid());

        /** @var User */
        $receiver = $this->buildUser($this->supermindRequest->getReceiverGuid());

        if (!$requester || !$receiver) {
            return;
        }

        switch ($this->topic) {
            case 'supermind_request_sent':
                $this->emailRecipient = $requester;
                $headerText = 'You sent a ' . $paymentString . ' Supermind offer to @' . $receiver->getUsername();
                $bodyText = 'They have 7 days to reply and accept this offer.';
                $ctaText = 'View Offer';
                $ctaPath = 'supermind/outbox?';
                $topic = $this->topic;
                break;

            case 'supermind_request_received':
                $this->emailRecipient = $receiver;
                $headerText = '@' . $requester->getUsername() . ' sent you a ' . $paymentString . ' Supermind offer';
                $bodyText = 'You have 7 days to reply and accept this offer.';
                $ctaText = 'View Offer';
                $ctaPath = 'supermind/inbox?';
                $topic = $this->topic;
                break;

            case 'supermind_request_accepted':
                $this->emailRecipient = $requester;
                $headerText = 'Congrats! @' . $receiver->getUsername() . ' replied to your Supermind offer';
                $bodyText = $paymentString . ' was sent to @' . $receiver->getUsername() . ' for their reply.';
                $ctaText = 'View Reply';
                $ctaPath = 'newsfeed/' . $this->supermindRequest->getActivityGuid() . '?';
                $topic = $this->topic;


                // Additional cta link/path
                $currency = $this->supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH ? 'cash' : 'tokens';

                $this->template->set('additionalCtaPath', '/wallet/' . $currency . '/transactions');
                $this->template->set('additionalCtaText', 'View Billing');

                break;

            case 'supermind_request_rejected':
                $this->emailRecipient = $requester;
                $headerText = '@' . $receiver->getUsername() . 'declined your Supermind offer';
                $bodyText = "Don't worry, you have not been charged. You can try increasing your offer specto improve your chance of reply";
                $ctaText = 'Learn more';
                $ctaPath = 'https://support.minds.com/hc/en-us/articles/9188136065684';
                $topic = $this->topic;
                break;

            case 'supermind_request_expiring':
                $this->emailRecipient = $receiver;
                $headerText = 'Your ' . $paymentString . ' Supermind offer expires tomorrow';
                $bodyText = "You have 24 hours remaining to review @" . $receiver->getUsername() . "'s" . $paymentString . "offer";
                $ctaText = 'View Offer';
                $ctaPath = 'supermind/inbox?';
                $topic = $this->topic;
                break;

            case 'supermind_request_expired':
                $this->emailRecipient = $requester;
                $headerText = 'Your Supermind offer to @' . $receiver->getUsername() . ' expired';
                $bodyText = "Don't worry, you have not been charged. You can try increasing your offer to improve your chance of reply";
                $ctaText = 'Learn more';
                $ctaPath = 'https://support.minds.com/hc/en-us/articles/9188136065684';
                $topic = $this->topic;
                break;

            default:
                return;
        }


        $tracking = [
            '__e_ct_guid' => $this->emailRecipient->getGUID(),
            'campaign' => 'when',
            'topic' => $this->topic,
            'state' => 'new',
        ];

        $trackingQuery = http_build_query($tracking);

        $this->template->set('user', $this->emailRecipient);
        $this->template->set('username', $this->emailRecipient->username);
        $this->template->set('email', $this->emailRecipient->getEmail());
        $this->template->set('guid', $this->emailRecipient->guid);
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', $bodyText);
        $this->template->set('bodyText', $bodyText);
        $this->template->set('headerText', $headerText);

        $actionButton = (new ActionButtonV2())
            ->setLabel($ctaText)
            ->setPath($ctaPath . $trackingQuery)
            ;

        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message
            ->setTo($this->emailRecipient)
            ->setMessageId(implode(
                '-',
                [ $this->emailRecipient->guid, sha1($this->emailRecipient->getEmail()), sha1($this->campaign . $this->topic . time()) ]
            ))
            ->setSubject($headerText)
            ->setHtml($this->template);

        return $message;
    }

    /**
     * @return void
     */
    public function send()
    {
        if ($this->emailRecipient && $this->emailRecipient->getEmail()) {
            // User is still not enabled

            $this->mailer->queue(
                $this->build(),
                true
            );

            $this->saveCampaignLog();
        }
    }

    /**
     * Build user from user guid
     * @param string $guid
     * @return User | void
     */
    public function buildUser($guid)
    {
        $user = $this->entitiesBuilder->single($guid);
        if ($user instanceof User) {
            return $user;
        }
    }

    /**
     * Build human-readable string consisting of payment method and amount
    * @param SupermindRequest $supermindRequest
     * @return string
     */
    public function buildPaymentString(SupermindRequest $supermindRequest): string
    {
        // Cash payments
        if ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH) {
            return "$" . $supermindRequest->getPaymentAmount();
        }
        // Token payments
        elseif ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::OFFCHAIN_TOKEN) {
            $currency = $supermindRequest->getPaymentAmount() != 1 ? 'tokens' : 'token';
            return $supermindRequest->getPaymentAmount() . $currency;
        }
    }
}
