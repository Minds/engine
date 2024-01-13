<?php
declare(strict_types=1);
namespace Minds\Core\MultiTenant\CustomPages\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\EntitiesBuilder;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\MultiTenant\CustomPages\Services\Service;
use Minds\Core\MultiTenant\CustomPages\Types\CustomPage;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * MultiTenant CustomPages controller
 */
class Controller
{
    public function __construct(
        private readonly Service $service
    ) {
    }

    /**
     * @param int $pageType
     * @return CustomPage
     */
    #[Query]
    public function getCustomPage(
        int $pageType
    ): CustomPage {
        // Convert the incoming int to the enum type
        return $this->service->getCustomPageByType(CustomPageTypesEnum::from($pageType));
    }

    /**
     * @param int $pageType
     * @param string|null $content
     * @param string|null $externalLink
     * @return bool
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function setCustomPage(
        int                 $pageType,
        ?string              $content,
        ?string              $externalLink,
        #[InjectUser] User  $loggedInUser,
    ): bool {
        // Convert the incoming integer to the enum type
        return $this->service->setCustomPage(
            CustomPageTypesEnum::from($pageType),
            content: $content,
            externalLink: $externalLink
        );
    }
}