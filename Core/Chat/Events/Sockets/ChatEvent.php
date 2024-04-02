<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Events\Sockets;

use Minds\Core\Chat\Events\Sockets\Enums\ChatEventTypeEnum;

class ChatEvent
{
    public function __construct(
        public ChatEventTypeEnum $type,
    ) {
    }
}
