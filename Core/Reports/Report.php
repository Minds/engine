<?php
/**
 * Reported Entity
 */

namespace Minds\Core\Reports;

use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\UserReports\UserReport;
use Minds\Entities\Entity;
use Minds\Traits\MagicAttributes;

/**
 * Class Report
 * @method int getEntityGuid()
 * @method string getEntityUrn()
 * @method Report setEntityUrn()
 * @method UserReport[] getReports()
 * @method Entity getEntity()
 * @method Report setEntity(Entity $value)
 * @method Report setEntityOwnerGuid(int $value)
 * @method boolean isAppeal()
 * @method Decision[] getInitialJuryDecisions()
 * @method Decision[] getAppealJuryDecisions()
 * @method int getAppealTimestamp()
 * @method int getReasonCode()
 * @method int getSubReasonCode()
 * @method int getTimestamp()
 * @method Report setTimestamp(int $timestamp)
 * @method Report setReasonCode(int $value)
 * @method Report setSubReasonCode(int $value)
 * @method string getOriginalEntity()
 * @method Report setOriginalEntity(string $value)
 * @method bool getUphold()
 * @method Report setUphold(bool $value)
 */
class Report
{
    use MagicAttributes;

    /** @var long $entityGuid */
    private $entityGuid;

    /** @var string $entityUrn */
    private $entityUrn;

    /** @var long $entityOwnerGuid */
    private $entityOwnerGuid;

    /** @var Entity $entity */
    private $entity;

    /** @var Entity $entity */
    private $originalEntity;

    /** @var int $timestamp */
    private $timestamp;

    /** @var array<UserReport> $reports */
    private $reports = [];

    /** @var array<Decision> $initialJuryDecisions */
    private $initialJuryDecisions = [];

    /** @var array<Decision> $appealJuryDecisions */
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
    private $stateChanges = [];

    /**
     * @param string $state pushes a state into our state change array
     * @return Report this
     */
    public function setState(string $state): Report
    {
        $this->stateChanges[] = $state;
        return $this;
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
        // before saving the report, the array has the following structure: ['reported', 'appealed'], but after
        // saving, it's like this: ['reported' => 1615383981]
        return !isset($sortedStates[0]) || is_numeric($sortedStates[0]) ? key($sortedStates) : $sortedStates[0];
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
    public function getUrn(): string
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
    public function export(): array
    {
        $export = [
            'urn' => $this->getUrn(),
            'entity_urn' => $this->entityUrn,
            'entity' => $this->entity ? $this->entity->export() : null,
            'original_entity' => $this->originalEntity ? $this->originalEntity->export() : null,
            /*'reports' => $this->reports ? array_map(function($report){
                return $report->export();
             }, $this->reports) : [],*/
            'is_appeal' => (bool) $this->isAppeal(),
            'appeal_note' => $this->getAppealNote(),
            'reason_code' => (int) $this->getReasonCode(),
            'sub_reason_code' => (int) $this->getSubReasonCode(),
            'state' => $this->getState(),
            'upheld' => $this->isUpheld(),
        ];

        return $export;
    }
}
