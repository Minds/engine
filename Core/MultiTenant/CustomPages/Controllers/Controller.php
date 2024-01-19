<?php
declare(strict_types=1);
namespace Minds\Core\MultiTenant\CustomPages\Controllers;

use Minds\Core\MultiTenant\CustomPages\Services\Service;
use Minds\Core\MultiTenant\CustomPages\Types\CustomPage;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * MultiTenant CustomPages controller
 */
class Controller
{
    public function __construct(
        private readonly Service $service
    ) {
    }

    #[Query]
    public function getCustomPage(
        string $pageType
    ): CustomPage {
        // Convert the incoming int to the enum type
        return $this->service->getCustomPageByType(CustomPageTypesEnum::from($pageType));
    }

    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function setCustomPage(
        string               $pageType,
        ?string              $content,
        ?string              $externalLink,
    ): bool {
        // Convert the incoming integer to the enum type
        return $this->service->setCustomPage(
            CustomPageTypesEnum::from($pageType),
            content: $content,
            externalLink: $externalLink
        );
    }
}
