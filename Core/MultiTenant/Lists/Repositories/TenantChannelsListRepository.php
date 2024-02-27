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

class TenantChannelsListRepository extends AbstractRepository implements TenantListRepositoryInterface
{
    /**
     * @return iterable
     * @throws ServerErrorException
     */
    public function getItems(): iterable
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(new RawExp('minds_entities_user u'))
            ->columns([
                'u.guid',
                'f.total_subscribers',
            ])
            ->leftJoin(
                function (SelectQuery $subQuery): void {
                    $subQuery
                        ->from('friends')
                        ->columns([
                            'friend_guid',
                            new RawExp('COUNT(*) as total_subscribers')
                        ])
                        ->innerJoin(new RawExp('minds_entities_user u'), 'guid', Operator::EQ, 'user_guid')
                        ->where('friend_guid', Operator::EQ, 'u.guid')
                        ->where('u.banned', Operator::EQ, 0)
                        ->where('u.deleted', Operator::EQ, 0)
                        ->where('u.enabled', Operator::EQ, 1)
                        ->groupBy('friend_guid')
                        ->alias('f');
                },
                'f.friend_guid',
                Operator::EQ,
                'u.guid'
            )
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->orderBy('f.total_subscribers DESC')
            ->prepare();

        try {
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            return $stmt->getIterator();
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Error fetching channels list', previous: $e);
        }
    }
}
