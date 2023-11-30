<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\GraphQL\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use RuntimeException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class TenantGuestModeFeedMySQLRepository extends AbstractRepository
{
    /**
     * @param int $tenantId
     * @param bool $onlyFeaturedUsers
     * @return int[]
     */
    public function getTopActivities(
        int $tenantId,
        int $limit = 12,
        int $offset = 0,
        bool $onlyFeaturedUsers = false,
        bool &$hasMore = false,
        ?string &$loadAfter = null
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(new RawExp('minds_entities e'))
            ->columns([
                'e.guid',
                'rank' => new RawExp("
                    (
                        SELECT COUNT(*) FROM minds_votes
                        WHERE minds_votes.entity_guid = e.guid
                        AND deleted = False
                        AND direction = 1
                    )
                    /
                    (NOW() - a.time_created)
                "),
            ])
            ->innerJoin(['a' => 'minds_entities_activity'], 'e.guid', Operator::EQ, 'a.guid')
            ->where('e.tenant_id', Operator::EQ, $tenantId)
            ->limit($limit + 1)
            ->offset($offset);

        if ($onlyFeaturedUsers) {
            $query->innerJoin(['feat_usrs' => 'minds_tenant_featured_entities'], "feat_usrs.entity_guid", Operator::EQ, "e.owner_guid");
        }

        $query->orderBy('rank DESC', 'a.time_created DESC');

        try {
            $statement = $query->execute();

            if ($statement->rowCount() > $limit) {
                $hasMore = true;
            }

            if ($loadAfter === null) {
                $loadAfter = $offset;
            }

            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $i => $row) {
                if ($i === $limit) {
                    break;
                }

                $loadAfter++;
                yield (int) $row['guid'];
            }
        } catch (RuntimeException $e) {
            $this->logger->error("An issue occurred whilst fetching top activities", [
                'exception' => $e,
            ]);

            return [];
        }
    }
}
