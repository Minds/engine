<?php
namespace Minds\Core\Notifications\PostSubscriptions\Models;

use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;

class PostSubscription
{
    public function __construct(
        public readonly int $userGuid,
        public readonly int $entityGuid,
        public PostSubscriptionFrequencyEnum $frequency,
    ) {
        
    }
}
