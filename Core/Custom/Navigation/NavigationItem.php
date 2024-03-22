<?php
namespace Minds\Core\Custom\Navigation;

use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Entities\ExportableInterface;

class NavigationItem implements ExportableInterface
{
    public function __construct(
        public readonly string $id,
        public string $name,
        public NavigationItemTypeEnum $type,
        public bool $visible,
        public string $iconId,
        public ?string $path = null,
        public ?string $url = null,
        public ?NavigationItemActionEnum $action = null,
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
