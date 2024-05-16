<?php

declare(strict_types=1);

/**
 * Boost emailer - handles the sending of emails for V3 boosts.
 */
namespace Minds\Core\Email\V2\Campaigns\Recurring\BoostV3;

use Exception;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Utils\BoostConsoleUrlBuilder;
use Minds\Core\Boost\V3\Utils\BoostReceiptUrlBuilder;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method self setTopic($topic)
 */
class BoostEmailer extends EmailCampaign
{
    use MagicAttributes;

    /** @var Boost */
    protected $boost;

    /** @var User */
    protected $user;

    /** @var string */
    protected $topic;

    public function __construct(
        protected $manager = null,
        private ?Template $template = null,
        private ?Mailer $mailer = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Config $config = null,
        private ?BoostConsoleUrlBuilder $consoleUrlBuilder = null,
        private ?BoostReceiptUrlBuilder $receiptUrlBuilder = null,
        private ?Logger $logger = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Email\Manager');
        $this->template ??= new Template();
        $this->mailer ??= new Mailer();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->config ??= Di::_()->get('Config');
        $this->consoleUrlBuilder ??= Di::_()->get(BoostConsoleUrlBuilder::class);
        $this->receiptUrlBuilder ??= Di::_()->get(BoostReceiptUrlBuilder::class);
        $this->logger ??= Di::_()->get('Logger');

        parent::__construct($this->manager);

        $this->campaign = 'when';
    }

    /**
     * Sets boost and returns a cloned instance of this class.
     * @param Boost $boost - boost to set
     * @return BoostEmailer cloned instance of class.
     */
    public function setBoost(Boost $boost): BoostEmailer
    {
        $boostEmailer = clone $this;
        $boostEmailer->boost = $boost;
        return $boostEmailer;
    }

    /**
     * Builds and sends email for given instance Boost and topic.
     * @return void
     * @throws Exception
     */
    public function send(): void
    {
        $msg = $this->build();

        if ($this->user && $this->user->getEmail()) {
            $this->mailer->send($msg);
            $this->saveCampaignLog();
        }
    }

    /**
     * Build email for instance Boost and the set topic.
     * @return Message build message.
     * @throws Exception
     */
    private function build(): ?Message
    {
        if (!$this->topic || !$this->boost) {
            return null;
        }

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');

        $this->user = $this->buildUser($this->boost->getOwnerGuid());

        if (!$this->user) {
            return null;
        }

        $trackingQueryParams = [
            '__e_ct_guid' => $this->user->getGuid(),
            'campaign' => 'when',
            'topic' => $this->topic,
        ];

        $trackingQueryString = http_build_query($trackingQueryParams);
        $receiptUrl = null;

        switch ($this->topic) {
            case ActionEvent::ACTION_BOOST_CREATED:
                $headerText = 'Your Boost is in review';
                $preHeaderText = "Here's what comes next.";
                $bodyText = "We're reviewing your Boost for {$this->getPaymentAmountString()} over {$this->getLengthDays()} days. Once it's approved, your Boost will automatically begin running.";
                $ctaText = 'View Status';
                $receiptUrl = $this->getBoostReceiptUrl();
                $ctaPath = $this->getConsoleUrl($trackingQueryParams);
                break;
            case ActionEvent::ACTION_BOOST_REJECTED:
                $ctaPath = $this->getConsoleUrl($trackingQueryParams);

                if ($this->boost->getTargetSuitability() === BoostTargetAudiences::SAFE) {
                    $headerText = 'Your Boost needs attention';
                    $preHeaderText = 'Please check the Boost console for more information.';
                    $bodyText = 'Your Boost has been reviewed, but was not approved. Please check the Boost console for more information. If your Boost was rejected for targeting the wrong audience, you can re-submit your Boost to another audience for approval.';
                    $ctaText = 'See Boost status';
                } elseif ($this->boost->getTargetSuitability() === BoostTargetAudiences::CONTROVERSIAL) {
                    $headerText = 'Your Boost was rejected';
                    $preHeaderText = 'Find out why.';
                    // $contentPolicyHtml = '<a href="https://support.minds.com/hc/en-us/articles/11723536774292-Boost-Content-Policy" style="color: #4080D0; text-decoration: underline" target="_blank">content policy</a>';
                    $bodyText = "We’ve reviewed your Boost and determined the content does not meet the content policy requirements for the selected audience. You have been refunded.";
                    $ctaText = 'View Results';
                } else {
                    $this->logger->error("Unsupported target suitability when sending email for Boost {$this->boost->getGuid()}");
                    return null;
                }
                break;

            case ActionEvent::ACTION_BOOST_ACCEPTED:
                $headerText = 'Your Boost is now running';
                $preHeaderText = "The Minds community is now seeing your Boost.";
                $bodyText = "Your Boost has been approved and is actively running. The campaign will end in {$this->getLengthDays()} day(s).";
                $ctaText = 'View Status';
                $ctaPath = $this->getConsoleUrl($trackingQueryParams);
                break;

            case ActionEvent::ACTION_BOOST_COMPLETED:
                $headerText = 'Your Boost is complete';
                $preHeaderText = "View the results.";
                $bodyText = "Your Boost for {$this->getPaymentAmountString()} over {$this->getLengthDays()} days is now complete. View the results and Boost again for even more reach and engagement.";
                $ctaText = 'View Results';
                $ctaPath = $this->getConsoleUrl($trackingQueryParams);
                break;
            default:
                return null;
        }

        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->getUsername());
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->getGuid());
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQueryString);
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', $preHeaderText);
        $this->template->set('bodyText', $bodyText);
        $this->template->set('headerText', $headerText);

        if ($receiptUrl) {
            $this->template->set('additionalCtaText', 'Receipt');
            $this->template->set('additionalCtaPath', $receiptUrl);
        } else {
            $this->template->set('additionalCtaText', '');
            $this->template->set('additionalCtaPath', '');
        }

        // Create action button
        $actionButton = (new ActionButtonV2())
            ->setLabel($ctaText)
            ->setPath($ctaPath);

        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message
            ->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [ $this->user->getGuid(), sha1($this->user->getEmail()), sha1($this->campaign . $this->topic . time()) ]
            ))
            ->setSubject($headerText)
            ->setHtml($this->template);

        return $message;
    }

    /**
     * Build user from user guid
     * @param string $guid - guid of the user
     * @return ?User user if one is found.
     */
    private function buildUser(string $guid): ?User
    {
        $user = $this->entitiesBuilder->single($guid);
        return $user instanceof User ? $user : null;
    }

    /**
     * Get duration of boost in days.
     * @return string length of boost in days.
     */
    private function getLengthDays(): string
    {
        return (string) $this->boost->getDurationDays();
    }

    /**
     * Gets console URL for clients to direct to on action.
     * @param array $queryParams - extra query params, e.g. for tracking.
     * @return string console URL to direct users to.
     */
    private function getConsoleUrl(array $queryParams = []): string
    {
        return $this->consoleUrlBuilder->build($this->boost, $queryParams);
    }

    /**
     * Gets receipt for a given boost.
     * @return string|null receipt URL for payment.
     */
    private function getBoostReceiptUrl(): ?string
    {
        return $this->receiptUrlBuilder->setBoost($this->boost)
            ->build();
    }

    /**
     * Gets the activity post URL for boost.
     * @return string activity post URL.
     */
    private function getActivityPostUrl(): string
    {
        $siteUrl = $this->config->get('site_url') ?: 'https://www.minds.com/';
        return "{$siteUrl}newsfeed/{$this->boost->getEntityGuid()}?";
    }

    /**
     * Build human-readable string consisting of payment method and amount
     * @return string - payment amount as a string.
     */
    private function getPaymentAmountString(): string
    {
        $paymentMethod = $this->boost->getPaymentMethod();
        // Cash payments
        if ($paymentMethod === BoostPaymentMethod::CASH) {
            return "$" . $this->boost->getPaymentAmount();
        }

        // Token payments
        elseif (in_array($paymentMethod, [BoostPaymentMethod::OFFCHAIN_TOKENS, BoostPaymentMethod::ONCHAIN_TOKENS], true)) {
            $currency = $this->boost->getPaymentAmount() != 1 ? ' tokens' : ' token';
            return round($this->boost->getPaymentAmount(), 2) . $currency;
        } else {
            throw new Exception("Unsupported payment method supplied");
        }
    }
}
