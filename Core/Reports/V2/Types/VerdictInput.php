<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Types;

use Minds\Core\Reports\Enums\ReportActionEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * Verdict input type.
 */
#[Input]
class VerdictInput
{
    public function __construct(
        #[Field(inputType: 'String')] public int $reportGuid,
        #[Field] public ReportActionEnum $action
    ) {
    }
}
