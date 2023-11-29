<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Types;

use Minds\Core\Feeds\RSS\Enums\RssFeedLastFetchStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class RssFeed
{
    public function __construct(
        #[Field(outputType: 'String!')] public readonly int $feedId,
        #[Field(outputType: 'String!')] public readonly int $userGuid,
        #[Field] public readonly string $title,
        #[Field] public readonly string $url,
        #[Field] public readonly ?int $tenantId = null,
        #[Field] public readonly ?int $createdAtTimestamp = null,
        #[Field] public readonly ?int $lastFetchAtTimestamp = null,
        #[Field] public readonly ?RssFeedLastFetchStatusEnum $lastFetchStatus = null,
        public ?int $lastFetchEntryTimestamp = null
    ) {
    }
}
