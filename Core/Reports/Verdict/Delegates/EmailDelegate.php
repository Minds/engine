<?php
/**
 * Email Notification delegate for Verdicts.
 */

namespace Minds\Core\Reports\Verdict\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Reports\Report;
use Minds\Common\Urn;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Core\Config;

class EmailDelegate
{
    /** @var Custom $campaign */
    protected $campaign;

    /** @var EntitiesBuilder $entitiesBuilder */
    protected $entitiesBuilder;

    /** @var Urn $urn */
    public function __construct($campaign = null, $entitiesBuilder = null, $urn = null)
    {
        $this->campaign = $campaign ?: new Custom();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->urn = $urn ?: new Urn();
    }

    /**
     * On Action.
     *
     * @param Report $report
     */
    public function onBan(Report $report)
    {
        $entityUrn = $report->getEntityUrn();
        $entityGuid = $this->urn->setUrn($entityUrn)->getNss();

        $entity = $this->entitiesBuilder->single($entityGuid);
        $owner = $entity->type === 'user' ? $entity : $this->entitiesBuilder->single($entity->getOwnerGuid());

        $type = $entity->type;
        if ($type === 'object') {
            $type = $entity->subtype;
        }

        $template = 'moderation-banned';

        $action = 'removed';

        switch ($report->getReasonCode()) {
            case 2:
                return;
                break;
            case 4:
            case 8:
                $template = 'moderation-3-strikes';
                break;
        }

        $reason = $this->getBanReasons($report);

        $subject = 'Account banned';

        $this->campaign->setUser($owner);
        $this->campaign->setTemplate($template);
        $this->campaign->setSubject($subject);
        $this->campaign->setTitle($title);
        $this->campaign->setPreheader('You have been banned');
        $this->campaign->setHideFooter(true);
        $this->campaign->setVars([
            'type' => $type,
            'action' => $action,
            'reason' => $reason,
        ]);

        $this->campaign->send();
    }

    /**
     * Returns a readable format for a given ban reason code, converting
     * tree indices to their text counterparts.
     *
     * e.g. with the default config, an index of 1 returns "Illegal"
     * an index of 1.3 returns "Illegal / Extortion"
     *
     * @param Report $report - the given ban report
     *
     * @return string the match from the ban reason tree, or
     *                if text is in the reason field, it will return that
     */
    public function getBanReasons(Report $report): string
    {
        $reason = implode('.', [$report->getReasonCode(), $report->getSubReasonCode()]);

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
                $reasonString .= ' / '.$subReasonObject[0]['label'];
            }

            return $reasonString;
        }

        return $reason;
    }
}
