<?php

namespace Spec\Minds\Core\Reports\V2\Validators;

use Minds\Core\Guid;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Illegal\SubReasonEnum as IllegalSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Nsfw\SubReasonEnum as NsfwSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Security\SubReasonEnum as SecuritySubReasonEnum;
use Minds\Core\Reports\V2\Types\ReportInput;
use Minds\Core\Reports\V2\Validators\ReportInputValidator;
use PhpSpec\ObjectBehavior;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class ReportInputValidatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ReportInputValidator::class);
    }

    public function it_should_be_enabled()
    {
        $this->isEnabled()->shouldBe(true);
    }

    // valid
    public function it_should_validate_a_valid_input()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::ILLEGAL;
        $illegalSubReason = IllegalSubReasonEnum::EXTORTION;
        $nsfwSubReason = null;
        $securitySubReason = null;

        $this->validate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ))->shouldBe(null);
    }

    // urn validation

    public function it_should_throw_an_exception_for_an_invalid_urn()
    {
        $entityUrn = 'invalid-urn';
        $reason = ReportReasonEnum::ILLEGAL;
        $illegalSubReason = IllegalSubReasonEnum::EXTORTION;
        $nsfwSubReason = null;
        $securitySubReason = null;

        $this->shouldThrow(
            new GraphQLException("Invalid entityUrn provided", 400, null, "Validation", ['field' => 'entityUrn'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    public function it_should_throw_an_exception_for_a_urn_with_no_guid_at_the_end()
    {
        $entityUrn = 'urn:activity:invalid';
        $reason = ReportReasonEnum::ILLEGAL;
        $illegalSubReason = IllegalSubReasonEnum::EXTORTION;
        $nsfwSubReason = null;
        $securitySubReason = null;

        $this->shouldThrow(
            new GraphQLException("entityGuid parsed from last URN segment is not numeric", 400, null, "Validation", ['field' => 'entityUrn'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    // illegal

    public function it_should_throw_an_exception_for_a_illegal_reason_with_no_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::ILLEGAL;
        $illegalSubReason = null;
        $nsfwSubReason = null;
        $securitySubReason = null;

        $this->shouldThrow(
            new GraphQLException("illegalSubReason must be passed if reason is ILLEGAL", 400, null, "Validation", ['field' => 'illegalSubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    public function it_should_throw_an_exception_for_a_illegal_reason_with_nsfw_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::ILLEGAL;
        $illegalSubReason = IllegalSubReasonEnum::EXTORTION;
        $nsfwSubReason = NsfwSubReasonEnum::NUDITY;
        $securitySubReason = null;

        $this->shouldThrow(
            new GraphQLException("nsfwSubReason enum passed for ILLEGAL reason", 400, null, "Validation", ['field' => 'nsfwSubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    public function it_should_throw_an_exception_for_a_illegal_reason_with_security_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::ILLEGAL;
        $illegalSubReason = IllegalSubReasonEnum::EXTORTION;
        $nsfwSubReason = null;
        $securitySubReason = SecuritySubReasonEnum::HACKED_ACCOUNT;

        $this->shouldThrow(
            new GraphQLException("securitySubReason enum passed for ILLEGAL reason", 400, null, "Validation", ['field' => 'securitySubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    // nsfw

    public function it_should_throw_an_exception_for_a_nsfw_reason_with_no_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::NSFW;
        $illegalSubReason = null;
        $nsfwSubReason = null;
        $securitySubReason = null;

        $this->shouldThrow(
            new GraphQLException("nsfwSubReason must be passed if reason is NSFW", 400, null, "Validation", ['field' => 'nsfwSubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    public function it_should_throw_an_exception_for_a_nsfw_reason_with_illegal_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::NSFW;
        $illegalSubReason = IllegalSubReasonEnum::EXTORTION;
        $nsfwSubReason = NsfwSubReasonEnum::NUDITY;
        $securitySubReason = null;

        $this->shouldThrow(
            new GraphQLException("illegalSubReason enum passed for NSFW reason", 400, null, "Validation", ['field' => 'illegalSubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    public function it_should_throw_an_exception_for_a_nsfw_reason_with_security_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::NSFW;
        $illegalSubReason = null;
        $nsfwSubReason = NsfwSubReasonEnum::NUDITY;
        $securitySubReason = SecuritySubReasonEnum::HACKED_ACCOUNT;

        $this->shouldThrow(
            new GraphQLException("securitySubReason enum passed for NSFW reason", 400, null, "Validation", ['field' => 'securitySubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    // nsfw

    public function it_should_throw_an_exception_for_a_security_reason_with_no_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::SECURITY;
        $illegalSubReason = null;
        $nsfwSubReason = null;
        $securitySubReason = null;

        $this->shouldThrow(
            new GraphQLException("securitySubReason must be passed if reason is SECURITY", 400, null, "Validation", ['field' => 'securitySubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    public function it_should_throw_an_exception_for_a_security_reason_with_illegal_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::SECURITY;
        $illegalSubReason = IllegalSubReasonEnum::EXTORTION;
        $nsfwSubReason = null;
        $securitySubReason = SecuritySubReasonEnum::HACKED_ACCOUNT;

        $this->shouldThrow(
            new GraphQLException("illegalSubReason enum passed for SECURITY reason", 400, null, "Validation", ['field' => 'illegalSubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }

    public function it_should_throw_an_exception_for_a_security_reason_with_nsfw_subreason()
    {
        $entityUrn = 'urn:activity:'.Guid::build();
        $reason = ReportReasonEnum::SECURITY;
        $illegalSubReason = null;
        $nsfwSubReason = NsfwSubReasonEnum::NUDITY;
        $securitySubReason = SecuritySubReasonEnum::HACKED_ACCOUNT;

        $this->shouldThrow(
            new GraphQLException("nsfwSubReason enum passed for SECURITY reason", 400, null, "Validation", ['field' => 'nsfwSubReason'])
        )->duringValidate(new ReportInput(
            entityUrn: $entityUrn,
            reason: $reason,
            illegalSubReason: $illegalSubReason,
            nsfwSubReason: $nsfwSubReason,
            securitySubReason: $securitySubReason
        ));
    }
}
