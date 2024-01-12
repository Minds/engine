<?php
namespace Minds\Core\MultiTenant\CustomPages\Services;

// ojm go through all files and ensure refs to roles, permissions, invites are gone
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use Minds\Core\MultiTenant\CustomPages\Repository;
use Minds\Entities\User;

/**
 * MultiTenant CustomPages service
 */
class Service
{
    public function __construct(
        private readonly Repository $repository,
    ) {
    }

    /**
     * Gets a custom page, given its page type
     * @param int $pageType
     * @return CustomPage
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getCustomPageByType(CustomPageTypesEnum $pageType): CustomPage
    {
        // Directly pass the enum type to the repository
        return $this->repository->getCustomPageByType($pageType);
    }

    /**
     * Sets a custom page
     */
    public function setCustomPage(CustomPageTypesEnum $pageType, ?string $content, ?string $externalLink): CustomPage
    {
        // Normalize content and external link - convert empty strings to null
        $normalizedContent = $content !== '' ? $content : null;
        $normalizedExternalLink = $externalLink !== '' ? $externalLink : null;

        return $this->repository->setCustomPage($pageType, $normalizedContent, $normalizedExternalLink);
    }
}