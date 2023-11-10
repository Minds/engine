<?php
declare(strict_types=1);

namespace Minds\Core\Http\Cloudflare\Models;

class CustomHostnameOwnershipVerification
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $value,
    ) {
    }
}
