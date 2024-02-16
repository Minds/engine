<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend\Models;

use Minds\Core\Notifications\Push\ManualSend\Enums\PushNotificationPlatformEnum;

/**
 * Object holding data for a manual send request.
 */
class ManualSendRequest
{
    public function __construct(
        public readonly string $userGuid,
        public readonly PushNotificationPlatformEnum $platform,
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly string $uri,
        public readonly string $iconUrl,
        public readonly string $mediaUrl,
        public readonly array $metadata
    ) {
    }
}
