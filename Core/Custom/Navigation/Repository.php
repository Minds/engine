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
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

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
            );
        }

        return $return;
    }

    /**
     * Returns the tenant id (-1 is host)
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
