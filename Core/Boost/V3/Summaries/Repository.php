<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use DateTime;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use PDO;
use PDOException;
use Selective\Database\RawExp;

class Repository extends AbstractRepository
{
    public const TABLE_NAME = 'boost_summaries';

    /**
     * Increments views
     */
    public function incrementViews(int $tenantId, int $guid, DateTime $date, int $views): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'guid' => new RawExp(':guid'),
                'date' => new RawExp(':date'),
                'views' => new RawExp(':views'),
            ])
            ->onDuplicateKeyUpdate([
                'views' => new RawExp('IFNULL(views, 0) + :views'),
            ]);

        $values = [
            'guid' => $guid,
            'date' => $date->format('c'),
            'views' => $views,
            'tenant_id' => $tenantId,
        ];

        $stmt = $query->prepare();
        return $stmt->execute($values);
    }

    /**
     * Increment clicks or insert new values for this timespan if none exist.
     * @param string $guid - guid key.
     * @param DateTime $date - date key.
     * @return bool - true on success.
     */
    public function incrementClicks(string $guid, DateTime $date): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'guid' => new RawExp(':guid'),
                'date' => new RawExp(':date'),
                'views' => 0,
                'clicks' => 1,
            ])
             ->onDuplicateKeyUpdate([
                'clicks' => new RawExp('IFNULL(clicks, 0) + +1'),
            ]);

        $values = [
            'guid' => $guid,
            'date' => $date->format('c'),
            'tenant_id' => $this->config->get('tenant_id') ?: -1
        ];

        $statement = $query->prepare();
        return $statement->execute($values);
    }


}
