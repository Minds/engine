<?php
/**
 * Supermind emailer
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\Supermind;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

class Supermind extends EmailCampaign
{
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var SupermindRequest */
    protected $supermindRequest;

    /** @var User */
    protected $user;

    /** @var string */
    protected $topic;

    /**
     * Constructor.
     * @param Template $template
     * @param Mailer $mailer
     * @param EntitiesBuilder $entitiesBuilder
     * @param Config $config
     */
    public function __construct(
        $template = null,
        $mailer = null,
        $entitiesBuilder = null,
        protected ?Config $config = null,
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * @return Message
     * @throws Exception
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

        $learnMorePath = 'https://support.minds.com/hc/en-us/articles/9188136065684';

        switch ($this->topic) {
            case 'supermind_request_sent':
                $this->user = $requester;
                $headerText = 'You sent a ' . $paymentString . ' Supermind offer to @' . $receiver->getUsername();
                $bodyText = 'They have 7 days to reply and accept this offer.';
                $ctaText = 'View Offer';
                $ctaPath = 'supermind/outbox?';
                break;

            case 'supermind_request_received':
                $this->user = $receiver;
                $headerText = '@' . $requester->getUsername() . ' sent you a ' . $paymentString . ' Supermind offer';
                $bodyText = 'You have 7 days to reply and accept this offer.';
                $ctaText = 'View Offer';
                $ctaPath = 'supermind/inbox?';
                break;

            case 'supermind_request_accepted':
                $this->user = $requester;
                $headerText = 'Congrats! @' . $receiver->getUsername() . ' replied to your Supermind offer';
                $bodyText = $this->buildPaymentString($this->supermindRequest, true) . ' was sent to @' . $receiver->getUsername() . ' for their reply.';
                $ctaText = 'View Reply';
                $ctaPath = 'newsfeed/' . $this->supermindRequest->getReplyActivityGuid() . '?';

                // Build path to wallet transactions table for selected currency
                $siteUrl = $this->config->get('site_url') ?: 'https://www.minds.com/';
                $currency = $this->supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH ? 'cash' : 'tokens';

                $this->template->set('additionalCtaPath', $siteUrl . 'wallet/' . $currency . '/transactions');
                $this->template->set('additionalCtaText', 'View Billing');

                break;

            case 'supermind_request_rejected':
                $this->user = $requester;
                $headerText = '@' . $receiver->getUsername() . ' declined your Supermind offer';
                $bodyText = "Don't worry, you have not been charged. You can try increasing your offer to improve your chance of reply";
                $ctaText = 'Learn more';
                $ctaPath = $learnMorePath;
                break;

            case 'supermind_request_expiring':
                $this->user = $receiver;
                $headerText = 'Your ' . $paymentString . ' Supermind offer expires tomorrow';
                $bodyText = "You have 24 hours remaining to review @" . $requester->getUsername() . "'s " . $paymentString . " offer";
                $ctaText = 'View Offer';
                $ctaPath = 'supermind/inbox?';
                break;

            case 'supermind_request_expired':
                $this->user = $requester;
                $headerText = 'Your Supermind offer to @' . $receiver->getUsername() . ' expired';
                $bodyText = "Don't worry, you have not been charged. You can try increasing your offer to improve your chance of reply";
                $ctaText = 'Learn more';
                $ctaPath = $learnMorePath;
                break;

            default:
                return;
        }


        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => 'when',
            'topic' => $this->topic,
            'state' => 'new',
        ];

        $trackingQuery = http_build_query($tracking);

        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', $bodyText);
        $this->template->set('bodyText', $bodyText);
        $this->template->set('headerText', $headerText);

        if (isset($ctaText) && isset($ctaPath)) {

            // Don't add tracking query to helpdesk links
            $actionButtonPath = ($this->topic == 'supermind_request_rejected' || $this->topic == 'supermind_request_expired') ? $ctaPath : $ctaPath . $trackingQuery;

            // Create action button
            $actionButton = (new ActionButtonV2())
                ->setLabel($ctaText)
                ->setPath($actionButtonPath)
                ;

            $this->template->set('actionButton', $actionButton->build());
        }

        $message = new Message();
        $message
            ->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [ $this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign . $this->topic . time()) ]
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
        $msg = $this->build();

        if ($this->user && $this->user->getEmail()) {
            // Send immediatly, as this is executed from a runner
            $this->mailer->send($msg);

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
    * @param bool $pluralize
     * @return string
     */
    public function buildPaymentString(SupermindRequest $supermindRequest, bool $pluralize = false): string
    {
        // Cash payments
        if ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::CASH) {
            return "$" . $supermindRequest->getPaymentAmount();
        }

        // Token payments
        elseif ($supermindRequest->getPaymentMethod() == SupermindRequestPaymentMethod::OFFCHAIN_TOKEN) {
            if ($pluralize) {
                $currency = $supermindRequest->getPaymentAmount() != 1 ? ' tokens' : ' token';
            } else {
                $currency = ' token';
            }

            return round($supermindRequest->getPaymentAmount(), 2) . $currency;
        } else {
            throw new Exception("Unsupported payment method supplied");
        }
    }
}
