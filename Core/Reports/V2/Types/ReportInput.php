<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Types;

use Minds\Core\Reports\Enums\Reasons\Illegal\SubReasonEnum as IllegalSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Nsfw\SubReasonEnum as NsfwSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Security\SubReasonEnum as SecuritySubReasonEnum;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * Report input type.
 */
#[Input]
class ReportInput
{
    public function __construct(
        #[Field] public string $entityUrn,
        #[Field] public ReportReasonEnum $reason,
        #[Field] public ?IllegalSubReasonEnum $illegalSubReason = null,
        #[Field] public ?NsfwSubReasonEnum $nsfwSubReason = null,
        #[Field] public ?SecuritySubReasonEnum $securitySubReason = null,
    ) {
    }

    /**
     * Utility function to get ReportReasonEnum version of subreason based upon
     * class properties. This cannot be a field as union types are not supported
     * on non-objects.
     * @return IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null - subreason.
     */
    public function getSubReason(): IllegalSubReasonEnum|NsfwSubReasonEnum|SecuritySubReasonEnum|null
    {
        return match ($this->reason) {
            ReportReasonEnum::ILLEGAL => $this->illegalSubReason ?? null,
            ReportReasonEnum::NSFW => $this->nsfwSubReason ?? null,
            ReportReasonEnum::SECURITY => $this->securitySubReason ?? null,
            default => null
        };
    }
}
