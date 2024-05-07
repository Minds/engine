<?php
namespace Minds\Core\Authentication\PersonalApiKeys;

use DateTimeImmutable;
use Minds\Core\Authentication\PersonalApiKeys\Enums\PersonalApiKeyScopeEnum;
use Minds\Core\Router\Enums\ApiScopeEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class PersonalApiKey
{
    #[Field]
    /** The 'secret' key that a user will use to authenticate with. Only returned once. */
    public string $secret = 'REDACTED';

    public function __construct(
        #[Field] public readonly string $id,
        public readonly int $ownerGuid,
        public readonly string $secretHash,
        #[Field] public readonly string $name,
        /** @var ApiScopeEnum[] */
        #[Field] public readonly array $scopes,
        #[Field] public readonly DateTimeImmutable $timeCreated,
        #[Field] public readonly ?DateTimeImmutable $timeExpires = null
    ) {
        
    }

    /**
     * Helper function to check if a scope is attached to the key
     */
    public function hasScope(ApiScopeEnum $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

}
