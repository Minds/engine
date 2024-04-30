<?php
namespace Minds\Core\Feeds\Elastic\V2;

use DateTime;
use Minds\Core\Feeds\Elastic\V2\Enums\MediaTypeEnum;
use Minds\Core\Feeds\Elastic\V2\Enums\SeenEntitiesFilterStrategyEnum;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class QueryOpts
{
    public function __construct(
        public readonly ?User $user = null,
        public readonly int $limit = 12,
        public readonly string $query = "",
        public readonly bool $onlySubscribed = false,
        public readonly bool $onlyGroups = false,
        public readonly bool $onlySubscribedAndGroups = false,
        public readonly bool $onlyOwn = false,
        public readonly ?int $accessId = null,
        public readonly ?MediaTypeEnum $mediaTypeEnum = MediaTypeEnum::ALL,
        public readonly ?array $nsfw = [],
        public readonly SeenEntitiesFilterStrategyEnum $seenEntitiesFilterStrategy = SeenEntitiesFilterStrategyEnum::NOOP,
        public readonly ?DateTime $olderThan = null,
    ) {
        $this->validateNsfwArray($nsfw);
    }

    /**
     * Validates that the nsfw array provided is valid
     */
    private function validateNsfwArray(array $nsfw): void
    {
        if (empty($nsfw)) {
            return; // This is fine, we can have an empty array
        }

        if (count(array_filter($nsfw, fn ($n) => !is_int($n))) > 0) {
            throw new ServerErrorException("NSFW can only be integers");
        }
    }
}
