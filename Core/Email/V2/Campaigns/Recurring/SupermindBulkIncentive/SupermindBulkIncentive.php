<?php
/**
 * SupermindBulkIncentive emailer
 */

namespace Minds\Core\Email\V2\Campaigns\Recurring\SupermindBulkIncentive;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Manager;
use Minds\Core\Email\Campaigns\EmailCampaign;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Partials\ActionButtonV2\ActionButtonV2;
use Minds\Core\Supermind\SupermindRequestReplyType;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Traits\MagicAttributes;

class SupermindBulkIncentive extends EmailCampaign
{
    use MagicAttributes;

    /** @var Template */
    protected $template;

    /** @var Mailer */
    protected $mailer;

    /** @var string */
    private $activityGuid;

    /** @var int */
    private $replyType = SupermindRequestReplyType::TEXT;

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

        $this->campaign = 'global';
        $this->topic = 'exclusive_promotions';
    }

    /**
     * @return SupermindBulkIncentive
     */
    public function withActivityGuid(string $activityGuid): SupermindBulkIncentive
    {
        $instance = clone $this;

        $instance->activityGuid = $activityGuid;

        return $instance;
    }

    /**
     * @return SupermindBulkIncentive
     */
    public function withReplyType(int $replyType): SupermindBulkIncentive
    {
        $instance = clone $this;

        if (!in_array($replyType, SupermindRequestReplyType::VALID_REPLY_TYPES, true)) {
            throw new ServerErrorException("You must provide a valid reply type");
        }

        $instance->replyType = $replyType;

        return $instance;
    }

    /**
     * Returns a sha1 hash that verifies the email was sent by minds.
     * Email rewards hook uses this to confirm validity
     * @return string
     */
    public function getValidatorToken(): string
    {
        $validator = [
            get_class($this), // Class name
            $this->user->getGUID(), // User guid email was sent to
            $this->activityGuid, // guid of the activity we create the supermind from
            $this->replyType,
            $this->config->get('emails_secret'),
        ];
        $validatorString = implode('', $validator);
        return sha1($validatorString);
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

        if (!$this->activityGuid) {
            throw new \Exception("You forgot to include the activityGuid");
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
            'activity_guid' => $this->activityGuid,
            'reply_type' => $this->replyType,
            'validator' => $this->getValidatorToken(),
        ];

        $trackingQuery = http_build_query($tracking);

        $headerText = "@{$this->user->getUsername()}, we want to send you a 5 token Supermind offer";

        $this->template->set('user', $this->user);
        $this->template->set('username', $this->user->username);
        $this->template->set('email', $this->user->getEmail());
        $this->template->set('guid', $this->user->guid);
        $this->template->set('campaign', $this->campaign);
        $this->template->set('topic', $this->topic);
        $this->template->set('tracking', $trackingQuery);
        $this->template->set('title', '');
        $this->template->set('state', '');
        $this->template->set('preheader', $headerText);
        $this->template->set('headerText', $headerText);
        
        $actionButtonPath = 'https://email.minds.com/supermind/inbox?'. $trackingQuery . '&utm_content=cta';

        // Create action button
        $actionButton = (new ActionButtonV2())
            ->setLabel("Let's do it")
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
            ->setSubject($headerText)
            ->setHtml($this->template);

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getEmailCampaignId()
    {
        return 'supermind_boffer_' . $this->activityGuid;
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
