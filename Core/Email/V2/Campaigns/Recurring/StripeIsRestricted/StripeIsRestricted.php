<?php
/**
 * StripeIsRestricted emailer
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\StripeIsRestricted;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Manager;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

class StripeIsRestricted extends EmailCampaign
{
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var User */
    protected $user;

    /** @var string */
    protected $topic;

    /** @var Manager */
    protected $manager;

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
        protected ?Config $config = null,
        ?Manager $manager = null
    ) {
        $this->template = $template ?: new Template();
        $this->mailer = $mailer ?: new Mailer();
        $this->config ??= Di::_()->get('Config');
        $this->manager = $manager ?? Di::_()->get('Email\Manager');

        $this->campaign = 'with';
        $this->topic = 'channel_improvement_tips';
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

        //

        $tracking = [
            '__e_ct_guid' => $this->user->getGUID(),
            'campaign' => 'when',
            'topic' => $this->topic,
            'state' => 'new',
            'utm_medium' => 'email',
            'utm_campaign' => $this->getEmailCampaignId(),
            'utm_source' => 'manual',
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
        $this->template->set('preheader', "Continue earning cash on Minds.");
        
        $actionButtonPath = 'https://email.minds.com/wallet/cash/settings?'. $trackingQuery . '&utm_content=cta';

        // Create action button
        $actionButton = (new ActionButtonV2())
            ->setLabel("Resolve issue")
            ->setPath($actionButtonPath)
            ;

        $this->template->set('actionButton', $actionButton->build());

        $message = new Message();
        $message
            ->setTo($this->user)
            ->setMessageId(implode(
                '-',
                [ $this->user->guid, sha1($this->user->getEmail()), sha1($this->campaign . $this->topic . time()) ]
            ))
            ->setSubject("Attention required: Your Minds cash account is currently restricted")
            ->setHtml($this->template);

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getEmailCampaignId()
    {
        return 'stripe_is_restricted';
    }

    /**
     * @return void
     */
    public function send()
    {
        $msg = $this->build();

        $canSend = $this->canSend() || true;

        if ($this->user && $this->user->getEmail() && $canSend) {
            // Send immediately, as this is executed from a runner
            $this->mailer->send($msg);

            $this->saveCampaignLog();
        }
    }
}
