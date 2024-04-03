<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Events\Sockets;

use JsonSerializable;
use Minds\Core\Chat\Events\Sockets\Enums\ChatEventTypeEnum;

class ChatEvent implements JsonSerializable
{
    public function __construct(
        public ChatEventTypeEnum $type,
        public array $metadata = []
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type->name,
            'metadata' => $this->metadata,
        ];
    }
}
