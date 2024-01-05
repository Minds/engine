<?php
namespace Minds\Core\Notifications\PostSubscriptions\Models;

use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class PostSubscription
{
    public function __construct(
        #[Field(outputType: "String!")] public readonly int $userGuid,
        #[Field(outputType: "String!")] public readonly int $entityGuid,
        #[Field] public PostSubscriptionFrequencyEnum $frequency,
    ) {
        
    }
}
