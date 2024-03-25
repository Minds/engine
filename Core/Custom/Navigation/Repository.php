<?php

namespace Minds\Core\Custom\Navigation;

use Minds\Core\Custom\Navigation\Enums\NavigationItemActionEnum;
use Minds\Core\Custom\Navigation\Enums\NavigationItemTypeEnum;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository extends AbstractRepository
{
    public const TABLE_NAME = 'minds_custom_navigation';

    /**
     * @return NavigationItem[]
     */
    public function getItems(): array
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'id',
                'name',
                'type',
                'visible',
                'icon_id',
                'path',
                'url',
                'action',
                'order',
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->orderBy(new RawExp('`order` desc'));

        $stmt = $query->prepare();
        $stmt->execute([
            'tenant_id' => $this->getTenantId(),
        ]);

        if ($stmt->rowCount() === 0) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $return = [];

        foreach ($rows as $row) {
            $return[] = new NavigationItem(
                id: $row['id'],
                name: $row['name'],
                type: constant(NavigationItemTypeEnum::class . '::' . $row['type']),
                visible: (bool) $row['visible'],
                iconId: $row['icon_id'],
                path: $row['path'],
                url: $row['url'],
                action: $row['action'] ? constant(NavigationItemActionEnum::class . '::' . $row['action']) : null,
                order: $row['order'],
            );
        }

        return $return;
    }
    
    /**
     * Upserts items to the database
     */
    public function addItem(NavigationItem $item): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'id' => new RawExp(':id'),
                'name' => new RawExp(':name'),
                'type' => new RawExp(':type'),
                'visible' => new RawExp(':visible'),
                'icon_id' => new RawExp(':icon_id'),
                'path' => new RawExp(':path'),
                'url' => new RawExp(':url'),
                'action' => new RawExp(':action'),
                'order' => new RawExp(':order'),
            ])
            ->onDuplicateKeyUpdate([
                'name' => new RawExp(':new_name'),
                'type' => new RawExp(':new_type'),
                'visible' => new RawExp(':new_visible'),
                'icon_id' => new RawExp(':new_icon_id'),
                'path' => new RawExp(':new_path'),
                'url' => new RawExp(':new_url'),
                'action' => new RawExp(':new_action'),
                'order' => new RawExp(':new_order'),
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type->name,
            'visible' => $item->visible,
            'icon_id' => $item->iconId,
            'path' => $item->path,
            'url' => $item->url,
            'action' => $item->action?->name,
            'order' => $item->order,
            // on duplicate...
            'new_name' => $item->name,
            'new_type' => $item->type->name,
            'new_visible' => $item->visible,
            'new_icon_id' => $item->iconId,
            'new_path' => $item->path,
            'new_url' => $item->url,
            'new_action' => $item->action?->name,
            'new_order' => $item->order,
        ]);
    }

    /**
     * Returns the tenant id (-1 is host)
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
