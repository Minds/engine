<?php
namespace Minds\Integrations\MemberSpace\Models;

class MemberSpaceProfile
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $name,
    ) {
        
    }
}
