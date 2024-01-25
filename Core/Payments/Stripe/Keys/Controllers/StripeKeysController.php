<?php
namespace Minds\Core\Payments\Stripe\Keys\Controllers;

use Minds\Core\GraphQL\Types\KeyValueType;
use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use Minds\Core\Payments\Stripe\Keys\Types\StripeKeysType;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class StripeKeysController
{
    public function __construct(
        private StripeKeysService $service
    ) {

    }

    /**
     * Set the stripe keys for the network
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function setStripeKeys(
        string $pubKey,
        string $secKey,
        #[InjectUser] User $loggedInUser // Do not add in docblock as it will break GraphQL
    ): bool {
        return $this->service->setKeys($pubKey, $secKey);
    }

    /**
     * Returns the stripe keys
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getStripeKeys(
        #[InjectUser] User $loggedInUser // Do not add in docblock as it will break GraphQL
    ): StripeKeysType {
        return new StripeKeysType(
            pubKey: $this->service->getPubKey(),
            secKey: 'REDACTED',
        );
    }
}
