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
        bool $onlyFeaturedUsers = false,
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(new RawExp('minds_entities e'))
            ->columns([
                'e.guid',
                'vote_count' => new RawExp("
                    CASE 
                        WHEN 
                            e.type='activity' AND (
                                SELECT COUNT(*) FROM minds_votes
                                WHERE minds_votes.entity_guid = e.guid
                                AND deleted = False
                                AND direction = 1
                            )
                        THEN TRUE 
                        ELSE FALSE
                    END
                "),
            ])
            ->innerJoin(['a' => 'minds_entities_activity'], 'e.guid', Operator::EQ, 'a.guid')
            ->where('e.tenant_id', Operator::EQ, $tenantId);

        if ($onlyFeaturedUsers) {
            $query->innerJoin(['feat_usrs' => 'minds_tenant_featured_entities'], "feat_usrs.entity_guid", Operator::EQ , "e.owner_guid");
        }

        $query->orderBy('vote_count DESC', 'a.time_created DESC');

        try {
            $statement = $query->execute();

            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
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

