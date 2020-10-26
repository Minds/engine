<?php
/**
 * Email Notification delegate for Strikes
 */
namespace Minds\Core\Reports\Strikes\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Reports\Strikes\Strike;
use Minds\Core\Events\EventsDispatcher;
use Minds\Common\Urn;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Core\Plus;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;

class EmailDelegate
{
    /** @var Custom */
    protected $campaign;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Urn */
    protected $urn;

    /** @var Plus\Manager */
    protected $plusManager;

    public function __construct($campaign = null, $entitiesBuilder = null, $urn = null, $plusManager = null)
    {
        $this->campaign = $campaign ?: new Custom;
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->urn = $urn ?: new Urn;
        $this->plusManager = $plusManager ?? Di::_()->get('Plus\Manager');
    }

    /**
     * On Strike
     * @param Strike $strike
     * @return void
     */
    public function onStrike(Strike $strike)
    {
        $entityUrn = $strike->getReport()->getEntityUrn();
        $entityGuid = $this->urn->setUrn($entityUrn)->getNss();

        $entity = $this->entitiesBuilder->single($entityGuid);
        $owner = $entity->type === 'user' ? $entity : $this->entitiesBuilder->single($entity->getOwnerGuid());

        $type = $entity->type;
        if ($type === 'object') {
            $type = $entity->subtype;
        }

        $action = 'removed';
        switch ($strike->getReasonCode()) {
            case 2:
                $action = 'marked as nsfw';
                break;
        }

        $subject = 'Strike received';

        $this->campaign->setUser($owner);
        $this->campaign->setTemplate($entity instanceof PaywallEntityInterface && $this->plusManager->isPlusEntity($entity) ? 'moderation-strike-plus' : 'moderation-strike');
        $this->campaign->setSubject($subject);
        $this->campaign->setTitle($subject);
        $this->campaign->setPreheader('You have received a strike');

        $this->campaign->setVars([
            'type' => $type,
            'action' => $action,
            //'reason' => $reason,
        ]);

        $this->campaign->send();
    }
}
