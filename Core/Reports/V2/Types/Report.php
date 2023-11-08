<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Types;

use Minds\Core\Comments\Comment;
use Minds\Core\Comments\GraphQL\Types\CommentEdge;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\Feeds\GraphQL\Types\ActivityEdge;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Groups\V2\GraphQL\Types\GroupEdge;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Illegal\SubReasonEnum as IllegalSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Nsfw\SubReasonEnum as NsfwSubReasonEnum;
use Minds\Core\Reports\Enums\Reasons\Security\SubReasonEnum as SecuritySubReasonEnum;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\Enums\ReportStatusEnum;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Report node type.
 */
#[Type]
class Report implements NodeInterface
{
    public function __construct(
        #[Field(outputType: 'String')] public int $tenantId,
        #[Field(outputType: 'String')] public int $reportGuid,
        #[Field(outputType: 'String')] public int $entityGuid,
        #[Field] public string $entityUrn,
        #[Field(outputType: 'String')] public int $reportedByGuid,
        #[Field] public int $createdTimestamp,
        #[Field] public ReportStatusEnum $status,
        #[Field] public ReportReasonEnum $reason,
        #[Field] public ?ReportActionEnum $action,
        #[Field] public ?IllegalSubReasonEnum $illegalSubReason = null,
        #[Field] public ?NsfwSubReasonEnum $nsfwSubReason = null,
        #[Field] public ?SecuritySubReasonEnum $securitySubReason = null,
        #[Field(outputType: 'String')] public ?int $moderatedByGuid = null,
        #[Field] public ?int $updatedTimestamp = null,
        #[Field] public ?string $cursor = ''
    ) {
    }

    /**
     * Gets ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID("report-" . $this->reportGuid);
    }

    /**
     * Gets entity edge from entityUrn.
     * @return ActivityEdge|UserEdge|GroupEdge|CommentEdge|null - entity edge.
     */
    #[Field]
    public function getEntityEdge(): ActivityEdge|UserEdge|GroupEdge|CommentEdge|null
    {
        $entity = Di::_()->get(EntitiesResolver::class)->single($this->entityUrn);

        switch(true) {
            case $entity instanceof Activity:
                return new ActivityEdge($entity, $this->cursor, false);
            case $entity instanceof Comment:
                return new CommentEdge($entity, $this->cursor);
            case $entity instanceof User:
                return new UserEdge($entity, $this->cursor);
            case $entity instanceof Group:
                return new GroupEdge($entity, $this->cursor);
            default:
                return null;
        }
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
