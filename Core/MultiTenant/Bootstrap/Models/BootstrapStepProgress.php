<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Models;

use JsonSerializable;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Traits\MagicAttributes;

/**
 * Model for storing the progress of a Bootstrapping step.
 * @method int getTenantId()
 * @method BootstrapStepEnum getStepName()
 * @method bool getSuccess()
 * @method \DateTime|null getLastRunTimestamp()
 * @method self setTenantId(int $tenantId)
 * @method self setStepName(BootstrapStepEnum $step)
 * @method self setSuccess(bool $success)
 * @method self setLastRunTimestamp(\DateTime|null $lastRunTimestamp)
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
        BootstrapStepEnum $stepName,
        bool $success,
        ?\DateTime $lastRunTimestamp = null
    ) {
        $this->tenantId = $tenantId;
        $this->stepName = $stepName;
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
            'stepName' => $this->stepName->name,
            'stepLoadingLabel' => $this->getStepLoadingLabel(),
            'success' => $this->success,
            'lastRunTimestamp' => $this->lastRunTimestamp?->getTimestamp(),
        ];
    }

    /**
     * Get the loading label of the step.
     * @return string - the loading label of the step.
     */
    public function getStepLoadingLabel(): string
    {
        return match ($this->stepName) {
            BootstrapStepEnum::TENANT_CONFIG_STEP => 'Configuring your network...',
            BootstrapStepEnum::CONTENT_STEP => 'Getting your content ready...',
            BootstrapStepEnum::LOGO_STEP => 'Building your logos...',
            BootstrapStepEnum::FINISHED => 'Your network is ready!',
            default => 'Loading...',
        };
    }
}
