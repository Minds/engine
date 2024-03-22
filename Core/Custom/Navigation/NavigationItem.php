<?php
namespace Minds\Core\Custom\Navigation;

use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Entities\ExportableInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class NavigationItem implements ExportableInterface
{
    public function __construct(
        #[Field] public readonly string $id,
        #[Field] public string $name,
        #[Field] public NavigationItemTypeEnum $type,
        #[Field] public bool $visible,
        #[Field] public string $iconId,
        #[Field] public ?string $path = null,
        #[Field] public ?string $url = null,
        #[Field] public ?NavigationItemActionEnum $action = null,
    ) {
        
    }

    /**
     * @inheritDoc
     */
    public function export(array $extras = []): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->name,
            'visible' => $this->visible,
            'iconId' => $this->iconId,
            'path' => $this->path,
            'url' => $this->url,
            'action' => $this->action?->name,
        ];
    }

}
