<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\CustomPages\Types;

use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Custom page node
 */
#[Type]
class CustomPage implements NodeInterface
{
    private int $tenantId;

    /**
     * @param CustomPageTypesEnum $pageType
     * @param string|null $content
     * @param string|null $externalLink
     * @param int $tenantId
     */
    public function __construct(
        #[Field] public readonly CustomPageTypesEnum $pageType,
        #[Field] public readonly ?string $content,
        #[Field] public readonly ?string $externalLink,
        int $tenantId
    ) {
        $this->tenantId = $tenantId;
    }
    /**
     * Gets ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    public function getId(): ID
    {
        return new ID("custom-page-{$this->tenantId}-{$this->pageType->value}");
    }
}
