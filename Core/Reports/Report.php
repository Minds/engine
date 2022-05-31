<?php
/**
 * Reported Entity
 */
namespace Minds\Core\Reports;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\UserReports\UserReport;
use Minds\Core\Session;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\Entity;
use Minds\Traits\MagicAttributes;

/**
 * Class Report
 * @method int getEntityGuid()
 * @method string getEntityUrn()
 * @method UserReport[] getReports()
 * @method Entity getEntity()
 * @method boolean isAppeal()
 * @method Decision[] getInitialJuryDecisions()
 * @method Decision[] getAppealJuryDecisions()
 * @method int getAppealTimestamp()
 * @method int getReasonCode()
 * @method int getSubReasonCode()
 * @method Report setState(string $string)
 * @method Report setTimestamp(int $timestamp)
 * @method Report setReasonCode(int $value)
 * @method Report setSubReasonCode(int $value)
 * @method int getTimestamp()
 * @method string getAppealNote()
 */
class Report
{
    use MagicAttributes;

    /** @var long $entityGuid  */
    private $entityGuid;

    /** @var string $entityUrn */
    private $entityUrn;

    /** @var long $entityOwnerGuid */
    private $entityOwnerGuid;

    /** @var Entity $entity  */
    private $entity;

    /** @var int $timestamp */
    private $timestamp;

    /** @var array<UserReport> $reports - reporting users */
    private $reports = [];

    /** @var array<Decisions> $initialJuryDecisions */
    private $initialJuryDecisions = [];

    /** @var array<Decisions> $appealJuryDecisions */
    private $appealJuryDecisions = [];

    /** @var boolean $uphold */
    private $uphold;

    /** @var boolean $appeal */
    private $appeal = false;

    /** @var int $appealTimestamp */
    private $appealTimestamp;

    /** @var string $appealNote */
    private $appealNote = '';

    /** @var int $reasonCode */
    private $reasonCode;

    /** @var int $subReasonCode */
    private $subReasonCode;

    /** @var array $userHashes */
    private $userHashes;

    /** @var array $stateChanges */
    private $stateChanges;
    
    /** @var $state */
    private $state;

    /**
     * Constructor.
     * @param ?EntitiesBuilder $entitiesBuilder - used to build entities.
     */
    public function __construct(private ?EntitiesBuilder $entitiesBuilder = null)
    {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    /**
     * Return the state of the report from the state changes
     */
    public function getState()
    {
        if (!$this->stateChanges) {
            return 'reported';
        }
        $sortedStates = $this->stateChanges;
        arsort($sortedStates);
        return key($sortedStates);
    }

    /**
     * Return if upheld
     * @return boolean | null
     */
    public function isUpheld()
    {
        return $this->uphold;
    }

    /**
     * Return the URN of this case
     * @return string
     */
    public function getUrn()
    {
        $parts = [
            "({$this->getEntityUrn()})",
            $this->getReasonCode(),
            $this->getSubReasonCode(),
            $this->getTimestamp(),
        ];
        return "urn:report:" . implode('-', $parts);
    }

    /**
     * @return array
     */
    public function export()
    {
        if ($this->entity instanceof PaywallEntityInterface) {
            $this->entity->setPayWall(true);
            $this->entity->setPaywallUnlocked(true);
        }
    
        $reportingUsers = [];

        if (Session::isAdmin()) {
            $reportingUsers = $this->getReportingUsers();
        }

        $export = [
            'urn' => $this->getUrn(),
            'entity_urn' => $this->entityUrn,
            'entity' => $this->entity ? $this->entity->export() : null,
            'reporting_users' => $reportingUsers,
            'reporting_users_count' => count($this->reports),
            'is_appeal' => (bool) $this->isAppeal(),
            'appeal_note' => $this->getAppealNote(),
            'reason_code' => (int) $this->getReasonCode(),
            'sub_reason_code' => (int) $this->getSubReasonCode(),
            'state' => $this->getState(),
            'upheld' => $this->isUpheld(),
        ];

        return $export;
    }

    /**
     * Gets a array of the reporting users for this report ('$this->reports').
     * @return array<User> array of reporting users.
     */
    protected function getReportingUsers(int $maxAmount = 5): array
    {
        $hydratedReportingUsers = [];

        foreach (array_slice($this->reports, 0, $maxAmount) as $reportingUser) {
            $hydratedReportingUser = $this->entitiesBuilder->single(
                $reportingUser->getReporterGuid()
            )->export();

            if ($hydratedReportingUser) {
                array_push($hydratedReportingUsers, $hydratedReportingUser);
            }
        }

        return $hydratedReportingUsers;
    }
}
