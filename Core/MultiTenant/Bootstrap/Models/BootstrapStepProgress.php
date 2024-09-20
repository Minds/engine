<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Models;

use JsonSerializable;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Traits\MagicAttributes;

/**
 * Model for storing the progress of a Bootstrapping step.
 * @method int getTenantId()
 * @method BootstrapStepEnum getStep()
 * @method bool getSuccess()
 * @method \DateTime getLastRunTimestamp()
 * @method self setTenantId(int $tenantId)
 * @method self setStep(BootstrapStepEnum $stepName)
 * @method self setSuccess(bool $success)
 * @method self setLastRunTimestamp(\DateTime $lastRunTimestamp)
 */
class BootstrapStepProgress implements JsonSerializable
{
    use MagicAttributes;

    /** @var int|null ID of the tenant. */
    protected ?int $tenantId;

    /** @var BootstrapStepEnum|null Name of the step. */
    protected ?BootstrapStepEnum $stepName;

    /** @var bool|null Whether the step was successful. */
    protected ?bool $success;

    /** @var \DateTime|null Last run timestamp. */
    protected ?\DateTime $lastRunTimestamp;

    public function __construct(
        int $tenantId,
        BootstrapStepEnum $step,
        bool $success,
        \DateTime $lastRunTimestamp
    ) {
        $this->tenantId = $tenantId;
        $this->stepName = $step;
        $this->success = $success;
        $this->lastRunTimestamp = $lastRunTimestamp;
    }

    /**
     * Export the model to an array.
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'stepName' => $this->stepName,
            'success' => $this->success,
            'lastRunTimestamp' => $this->lastRunTimestamp->getTimestamp(),
        ];
    }
}
