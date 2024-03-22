<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Validators;

use Minds\Common\Urn;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\V2\Types\ReportInput;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

/**
 * Validator for a ReportInput.
 */
class ReportInputValidator implements InputTypeValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Validates the input type for a ReportInput.
     * @throws GraphQLException
     */
    public function validate(object $reportInput): void
    {
        if (!($reportInput instanceof ReportInput)) {
            return;
        }

        // check URN is valid.
        if (!Urn::isValid($reportInput->entityUrn)) {
            throw new GraphQLException("Invalid entityUrn provided", 400, null, "Validation", ['field' => 'entityUrn']);
        }

        // check a GUID can be parsed from URN.
        $entityUrnSegments = explode(':', $reportInput->entityUrn);
        if (!is_numeric($guidSegments = $entityUrnSegments[array_key_last($entityUrnSegments)])) {
            if ($segments = explode('_', $guidSegments)) {
                if (!is_numeric($segments[0]) || !is_numeric($segments[1] ?? null)) {
                    throw new GraphQLException("entityGuid parsed from last URN segment is not numeric", 400, null, "Validation", ['field' => 'entityUrn']);
                }
            }
        }

        // check illegal reason has valid subreason.
        if ($reportInput->reason === ReportReasonEnum::ILLEGAL) {
            if (!$reportInput->illegalSubReason) {
                throw new GraphQLException("illegalSubReason must be passed if reason is ILLEGAL", 400, null, "Validation", ['field' => 'illegalSubReason']);
            }

            if ($reportInput->nsfwSubReason) {
                throw new GraphQLException("nsfwSubReason enum passed for ILLEGAL reason", 400, null, "Validation", ['field' => 'nsfwSubReason']);
            }

            if ($reportInput->securitySubReason) {
                throw new GraphQLException("securitySubReason enum passed for ILLEGAL reason", 400, null, "Validation", ['field' => 'securitySubReason']);
            }
        }

        // check nsfw reason has valid subreason.
        if ($reportInput->reason === ReportReasonEnum::NSFW) {
            if (!$reportInput->nsfwSubReason) {
                throw new GraphQLException("nsfwSubReason must be passed if reason is NSFW", 400, null, "Validation", ['field' => 'nsfwSubReason']);
            }

            if ($reportInput->illegalSubReason) {
                throw new GraphQLException("illegalSubReason enum passed for NSFW reason", 400, null, "Validation", ['field' => 'illegalSubReason']);
            }

            if ($reportInput->securitySubReason) {
                throw new GraphQLException("securitySubReason enum passed for NSFW reason", 400, null, "Validation", ['field' => 'securitySubReason']);
            }
        }

        // check security reason has valid subreason.
        if ($reportInput->reason === ReportReasonEnum::SECURITY) {
            if (!$reportInput->securitySubReason) {
                throw new GraphQLException("securitySubReason must be passed if reason is SECURITY", 400, null, "Validation", ['field' => 'securitySubReason']);
            }

            if ($reportInput->illegalSubReason) {
                throw new GraphQLException("illegalSubReason enum passed for SECURITY reason", 400, null, "Validation", ['field' => 'illegalSubReason']);
            }

            if ($reportInput->nsfwSubReason) {
                throw new GraphQLException("nsfwSubReason enum passed for SECURITY reason", 400, null, "Validation", ['field' => 'nsfwSubReason']);
            }
        }

        return;
    }
}
