<?php
namespace Minds\Core\Notifications\Push\Config;

class PushNotificationConfig
{
    public function __construct(
        public readonly string $apnsTeamId,
        public readonly string $apnsKey,
        public readonly string $apnsKeyId,
        public readonly string $apnsTopic,
    ) {
        
    }
}
