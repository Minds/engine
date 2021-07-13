<?php

/**
 * Email Notification delegate for Verdicts
 */

namespace Minds\Core\Reports\Verdict\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Reports\Report;
use Minds\Common\Urn;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Core\Config;
use Minds\Entities\User;

class EmailDelegate
{
    /** @var Custom $campaign */
    protected $campaign;

    /** @var EntitiesBuilder $entitiesBuilder */
    protected $entitiesBuilder;

    /** @var Urn $urn */
    protected $urn;

    /** @var Config */
    protected $config;

    public function __construct($campaign = null, $entitiesBuilder = null, $urn = null, $config = null)
    {
        $this->campaign = $campaign ?: new Custom;
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->urn = $urn ?: new Urn;
        $this->config = $config ?: Di::_()->get('Config');
    }


    /**
     * On hacked account
     * @param Report $report
     * @param string $randomPassword
     * @return void
     */
    public function onHack(Report $report)
    {
        $entityUrn = $report->getEntityUrn();
        $entityGuid = $this->urn->setUrn($entityUrn)->getNss();

        $entity = $this->entitiesBuilder->single($entityGuid);
        $owner = $entity->type === 'user' ? $entity : $this->entitiesBuilder->single($entity->getOwnerGuid());

        $template = 'hacked-account';

        $subject = 'Account compromised';

        $this->campaign->setUser($owner);
        $this->campaign->setTemplate($template);
        $this->campaign->setSubject($subject);
        $this->campaign->setTitle($subject);
        $this->campaign->setPreheader('Your account security has been compromised');
        $this->campaign->setHideDownloadLinks(true);

        $this->campaign->send();
    }

    /**
     * On Action
     * @param Report $report
     * @return void
     */
    public function onBan(Report $report)
    {
        $entityUrn = $report->getEntityUrn();
        $entityGuid = $this->urn->setUrn($entityUrn)->getNss();

        $entity = $this->entitiesBuilder->single($entityGuid);
        $owner = $entity->type === 'user' ? $entity : $this->entitiesBuilder->single($entity->getOwnerGuid());

        $template = 'moderation-banned';

        switch ($report->getReasonCode()) {
            case 2:
                return;
                break;
            case 4:
            case 8:
                // Direct reports/bans on User are automatic.
                // Reports/bans on entities are because of 3 strikes.
                if (!$entity instanceof User) {
                    $template = 'moderation-3-strikes';
                }
                break;
        }

        $subject = 'Account banned';

        $reasonCode = implode('.', [$report->getReasonCode(), $report->getSubReasonCode()]);

        $reason = $this->getBanReasons($reasonCode);

        $this->campaign->setUser($owner);
        $this->campaign->setTemplate($template);
        $this->campaign->setSubject($subject);
        $this->campaign->setTitle($subject);
        $this->campaign->setPreheader('You have been banned from Minds');
        $this->campaign->setHideDownloadLinks(true);
        $this->campaign->setVars([
            'reason' => $reason,
        ]);

        $this->campaign->send();
    }


    /**
     * @return Custom
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Returns a readable format for a given ban reason, converting
     * tree indices to their text counterparts.
     *
     * e.g. with the default config, an index of 1 returns "Illegal"
     * an index of 1.3 returns "Illegal / Extortion"
     *
     * @param string $index - the given ban reason index
     * @return string the match from the ban reason tree, or
     *  if text is in the reason field, it will return that.
     */
    public function getBanReasons($reason): string
    {
        $banReasons = Di::_()->get('Config')->get('report_reasons');
        $splitReason = preg_split("/\./", $reason);

        if (is_numeric($reason) && isset($splitReason[0])) {
            // get filter out matching reason and re-index array from 0.
            $reasonObject = array_values(array_filter(
                $banReasons,
                function ($r) use ($splitReason) {
                    return (string) $r['value'] === $splitReason[0];
                }
            ));
            // start string with matching label
            $reasonString = $reasonObject[0]['label'];
            // if has more, and the reason supplied requests more (e.g. 1.1)
            if ($reasonObject[0]['hasMore'] && isset($splitReason[1])) {
                // filter for sub-reasons.
                $subReasonObject = array_values(array_filter(
                    $reasonObject[0]['reasons'],
                    function ($sub) use ($splitReason) {
                        return (string) $sub['value'] === $splitReason[1];
                    }
                ));
                $reasonString .= ' - ' . $subReasonObject[0]['label'];
            }
            return $reasonString;
        }
        return $reason;
    }
}
