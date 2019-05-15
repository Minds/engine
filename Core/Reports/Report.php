<?php
/**
 * Reported Entity
 */
namespace Minds\Core\Reports;

use Minds\Core\Reports\UserReport;
use Minds\Traits\MagicAttributes;

/**
 * Class Report
 * @method Report getEntityGuid(): long
 * @method Report getEntityUrn(): string
 * @method Report getReports(): []
 * @method Report getEntity(): Entity
 * @method Report isAppeal(): boolean
 * @method Report getInitialJuryDecisions: []
 * @method Report getAppealJuryDecisions: []
 * @method Report getAppealTimestamp: int
 * @method Report getReasonCode(): int
 * @method Report getSubReasonCode(): int
 * @method Report setState(string $string)
 * @method Report getState(): string
 * @method Report setTimestamp(int $timestamp)
 * @method Report setReasonCode(int $value)
 * @method Report setSubReasonCode(int $value)
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

    /** @var array<UserReport> $reports */
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
        $export = [
            'urn' => $this->getUrn(),
            'entity_urn' => $this->entityUrn,
            'entity' => $this->entity ? $this->entity->export() : null,
            /*'reports' => $this->reports ? array_map(function($report){
                return $report->export();
             }, $this->reports) : [],*/
            'is_appeal' => (bool) $this->isAppeal(),
            'appeal_note' => $this->getAppealNote(),
            'reason_code' => (int) $this->getReasonCode(),
            'sub_reason_code' => (int) $this->getSubReasonCode(),
            'state' => $this->getState(),
        ];

        return $export;
    }

}
