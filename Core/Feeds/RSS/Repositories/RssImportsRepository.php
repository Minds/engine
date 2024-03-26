<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Repositories;

use DateTime;
use DateTimeInterface;
use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Feeds\RSS\Enums\RssFeedLastFetchStatusEnum;
use Minds\Core\Feeds\RSS\Exceptions\RssFeedNotFoundException;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Guid;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Zend\Diactoros\Uri;

class RssImportsRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_user_rss_imports';

    /**
     * Add an entry paring of the rss entry to the activity guid
     */
    public function addEntry(int $feedId, string $url, int $activityGuid)
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'feed_id' => new RawExp(':feed_id'),
                'url' => new RawExp(':url'),
                'activity_guid' => new RawExp(':activity_guid'),
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'feed_id' => $feedId,
            'url' => $url,
            'activity_guid' => $activityGuid,
        ]);
    }

    /**
     * Returns true if an entity has already been paired for the url
     */
    public function hasMatch(int $feedId, string $url): bool
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('feed_id', Operator::EQ, new RawExp(':feed_id'))
            ->where('url', Operator::EQ, new RawExp(':url'));

        $stmt = $query->prepare();

        $stmt->execute([
            'tenant_id' => $this->getTenantId(),
            'feed_id' => $feedId,
            'url' => $url,
        ]);

        return $stmt->rowCount() >= 1;
    }

    /**
     * Returns the tenant id, uses -1 if host site
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
