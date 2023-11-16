<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Repositories;

use DateTime;
use DateTimeInterface;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Feeds\RSS\Enums\RssFeedLastFetchStatusEnum;
use Minds\Core\Feeds\RSS\Exceptions\RssFeedNotFoundException;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Zend\Diactoros\Uri;

class MySQLRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_user_rss_feeds';

    public function __construct(
        Client $mysqlHandler,
        Logger $logger,
        private readonly Config $config
    ) {
        parent::__construct($mysqlHandler, $logger);
    }

    /**
     * @param Uri $rssFeedUrl
     * @param User $user
     * @return bool
     * @throws ServerErrorException
     */
    public function createRssFeed(
        Uri $rssFeedUrl,
        string $title,
        User $user
    ): bool {
        $statement = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'feed_id' => Guid::build(),
                'user_guid' => new RawExp(':user_guid'),
                'tenant_id' => new RawExp(':tenant_id'),
                'title' => new RawExp(':title'),
                'url' => new RawExp(':url'),
            ])
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'user_guid' => (int) $user->getGuid(),
            'tenant_id' => $this->config->get('tenant_id') ?? 0,
            'title' => $title,
            'url' => (string) $rssFeedUrl,
        ]);


        return $statement->execute();
    }

    /**
     * @param int $feedId
     * @return RssFeed
     * @throws RssFeedNotFoundException
     * @throws ServerErrorException
     */
    public function getFeed(int $feedId): RssFeed
    {
        $statement = $this->buildBaseFeedQuery()
            ->where('feed_id', Operator::EQ, new RawExp(':feed_id'))
            ->prepare();
        if (!$statement->execute([
            'feed_id' => $feedId,
        ])) {
            throw new ServerErrorException('Failed to fetch feed');
        }

        if ($statement->rowCount() === 0) {
            throw new RssFeedNotFoundException();
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->buildRssFeedObject($row);
    }

    /**
     * @param User $user
     * @return RssFeed[]
     * @throws ServerErrorException
     */
    public function getFeeds(
        ?User $user = null,
    ): iterable {
        $statement = $this->buildBaseFeedQuery();
        $params = [];

        if ($user) {
            $statement->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
            $params['user_guid'] = (int) $user->getGuid();
        }

        $statement = $statement->prepare();

        if (!$statement->execute($params)) {
            throw new ServerErrorException('Failed to fetch feeds');
        }

        if ($statement->rowCount() === 0) {
            return [];
        }

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield $this->buildRssFeedObject($row);
        }
    }

    private function buildBaseFeedQuery(): SelectQuery
    {
        return $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->orderBy('last_fetch_at DESC', 'created_at ASC');
    }

    private function buildRssFeedObject(array $data): RssFeed
    {
        return new RssFeed(
            feedId: (int) $data['feed_id'],
            userGuid: (int) $data['user_guid'],
            title: (string) $data['title'],
            url: (string) $data['url'],
            tenantId: $data['tenant_id'] ? (int) $data['tenant_id'] : null,
            createdAtTimestamp: strtotime($data['created_at']),
            lastFetchAtTimestamp: $data['last_fetch_at'] ? strtotime($data['last_fetch_at']) : null,
            lastFetchStatus: RssFeedLastFetchStatusEnum::tryFrom((int) $data['last_fetch_status']),
            lastFetchEntryTimestamp: $data['last_fetch_entry_timestamp'] ? strtotime($data['last_fetch_entry_timestamp']) : null,
        );
    }

    public function removeRssFeed(int $feedId): bool
    {
        $statement = $this->mysqlClientWriterHandler->delete()
            ->from(self::TABLE_NAME)
            ->where('feed_id', Operator::EQ, new RawExp(':feed_id'))
            ->prepare();

        return $statement->execute([
            'feed_id' => $feedId,
        ]);
    }

    public function updateRssFeed(
        int                             $feedId,
        DateTime|DateTimeInterface|null $lastFetchEntryDate,
        RssFeedLastFetchStatusEnum      $status
    ): bool {
        $statement = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set([
                'last_fetch_at' => date('c', time()),
                'last_fetch_status' => $status->value,
                'last_fetch_entry_timestamp' => date('c', $lastFetchEntryDate?->getTimestamp())
            ])
            ->where('feed_id', Operator::EQ, new RawExp(':feed_id'))
            ->prepare();

        return $statement->execute([
            'feed_id' => $feedId,
        ]);
    }
}
