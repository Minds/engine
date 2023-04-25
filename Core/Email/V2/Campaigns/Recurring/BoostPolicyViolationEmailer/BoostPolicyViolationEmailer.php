<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Campaigns\Recurring\BoostPolicyViolationEmailer;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Utils\BoostConsoleUrlBuilder;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * Emailer for Boost policy violations on a given entity.
 * @method self setUser(User $user)
 * @method User getUser()
 * @method Activity|User getEntity()
 * @method self setEntity(Activity|User $entity)
 */
class BoostPolicyViolationEmailer extends EmailCampaign
{
    use MagicAttributes;

    /** @var User */
    protected $user;

    /** @var Entity|User */
    protected $entity;

    /** @var string */
    protected $campaign = 'when';

    /** @var string */
    protected $topic = 'boost_policy_violation';

    public function __construct(
        protected $manager = null,
        private ?Template $template = null,
        private ?Mailer $mailer = null,
        private ?BoostConsoleUrlBuilder $consoleUrlBuilder = null,
    ) {
        $this->manager = $manager ?? Di::_()->get('Email\Manager');
        $this->template ??= new Template();
        $this->mailer ??= new Mailer();
        $this->consoleUrlBuilder ??= Di::_()->get(BoostConsoleUrlBuilder::class);
    }

    /**
     * Builds and sends the email.
     * @throws Exception
     * @return void
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
     * Builds and queues sending of the email.
     * @return void
     * @throws Exception
     */
    public function queue(): void
    {
        $msg = $this->build();

        if ($this->user && $this->user->getEmail()) {
            $this->mailer->queue($msg);
            $this->saveCampaignLog();
        }
    }


    /**
     * Build email message for instance entity and user.
     * @return Message built email message.
     * @throws Exception
     */
    private function build(): ?Message
    {
        if (!$this->user || !$this->entity) {
            return null;
        }

        $this->template->setTemplate('default.v2.tpl');
        $this->template->setBody('./template.tpl');

        $trackingQueryParams = [
            '__e_ct_guid' => $this->user->getGuid(),
            'campaign' => 'when',
            'topic' => $this->topic,
        ];

        $trackingQueryString = http_build_query($trackingQueryParams);

       
        $headerText = 'Unfortunately your Boost has been canceled';
        $preHeaderText = "Your Boost has been canceled";

        $boostContentPolicyAnchorTag = '<a href="https://support.minds.com/hc/en-us/articles/11723536774292-Boost-Content-Policy" target="_blank">Boost content policy</a>.';
        $bodyText = "Your in-progress Boost has been canceled due to issues with the $boostContentPolicyAnchorTag";

        $ctaText = 'See Boost status';
        $ctaPath = $this->getConsoleUrl($this->entity, $trackingQueryParams);

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
     * Gets console URL to direct a user to the appropriate area of their console.
     * @param array $queryParams - extra query params, e.g. for tracking.
     * @return string console URL to direct users to.
     */
    private function getConsoleUrl(mixed $entity, array $queryParams = []): string
    {
        return $this->consoleUrlBuilder->buildWithFilters(
            BoostStatus::REJECTED,
            $entity instanceof User ? BoostTargetLocation::SIDEBAR : BoostTargetLocation::NEWSFEED,
            $queryParams
        );
    }
}
