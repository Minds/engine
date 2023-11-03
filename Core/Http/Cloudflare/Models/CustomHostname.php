<?php
declare(strict_types=1);

namespace Minds\Core\Http\Cloudflare\Models;

use Minds\Core\Http\Cloudflare\Enums\CustomHostnameStatusEnum;

class CustomHostname
{
    public function __construct(
        public readonly string $id,
        public readonly string $hostname,
        public readonly string $customOriginServer,
        public readonly CustomHostnameStatusEnum $status,
        public readonly CustomHostnameMetadata $metadata,
        public readonly CustomHostnameOwnershipVerification $ownershipVerification,
        public readonly int $createdAt,
    ) {
    }
}
