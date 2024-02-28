<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class TenantGroupsListRepository extends AbstractRepository implements TenantListRepositoryInterface
{
    public function getItems(): iterable
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(new RawExp('minds_entities_group g'))
            ->columns([
                'g.guid',
                'gm.total_members',
            ])
            ->leftJoin(
                function (SelectQuery $subQuery): void {
                    $subQuery
                        ->from('minds_group_membership')
                        ->columns([
                            'group_guid',
                            new RawExp('COUNT(user_guid) as total_members')
                        ])
                        ->groupBy('group_guid')
                        ->alias('gm');
                },
                'gm.group_guid',
                Operator::EQ,
                'g.guid'
            )
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->orderBy('gm.total_members DESC')
            ->limit(150)
            ->prepare();

        try {
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            return $stmt->getIterator();
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Error fetching groups list', previous: $e);
        }
    }
}
