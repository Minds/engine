<?php
namespace Minds\Core\Groups\V2\Membership;

use DateTime;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Data\MySQL;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Log\Logger;
use Minds\Exceptions\NotFoundException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class Repository extends MySQL\AbstractRepository
{
    const CACHE_KEY_PREFIX = "group:v2:membership";

    public function __construct(
        MySQL\Client $mysqlClient,
        Config $config,
        Logger $logger,
        protected PsrWrapper $cache
    ) {
        parent::__construct($mysqlClient, $config, $logger);
    }

    /**
     * Returns a single membership
     */
    public function get(
        int $groupGuid,
        int $userGuid,
    ): Membership {
        $cacheKey = $this->getMembershipCacheKey($groupGuid, $userGuid);

        // Return from cache if possible
        if ($cached = $this->cache->get($cacheKey)) {
            return unserialize($cached);
        }

        $rows = iterator_to_array($this->getList(
            groupGuid: $groupGuid,
            userGuid: $userGuid,
            limit: 1,
        ));

        if (empty($rows)) {
            throw new NotFoundException("User doesn't appear to be in the group");
        }

        $membership = $rows[0];

        // Update the cache
        $this->cache->set($cacheKey, serialize($membership));

        return $membership;
    }

    /**
     * Returns membership states
     * @return iterable<Membership>
     */
    public function getList(
        int $groupGuid = null,
        int $userGuid = null,
        GroupMembershipLevelEnum $membershipLevel = null,
        bool $membershipLevelGte = false,
        int $limit = 12,
        int $offset = 0,
    ): iterable {
        $values = [];

        $inferredByMembershipQuery = $this->mysqlClientReaderHandler->select()
            ->columns([
                'group_guid' => 'mga.group_guid',
                'user_guid' => 's.user_guid',
                'created_timestamp' => 's.valid_from',
                'membership_level' => new RawExp(GroupMembershipLevelEnum::MEMBER->value),
            ])
            ->from(new RawExp('minds_site_membership_tiers_group_assignments mga'))
            ->innerJoin(['s' => 'minds_site_membership_subscriptions'], 's.membership_tier_guid', Operator::EQ, 'mga.membership_tier_guid')
            ->leftJoinRaw('minds_group_membership', 'minds_group_membership.group_guid = mga.group_guid AND minds_group_membership.user_guid = s.user_guid')
            ->where('minds_group_membership.group_guid', Operator::IS, null)
            ->where('minds_group_membership.user_guid', Operator::IS, null);

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'group_guid',
                'user_guid',
                'created_timestamp' => new RawExp('MIN(created_timestamp)'),
                'membership_level' => new RawExp('MIN(membership_level)')
            ])
            ->from(function (SelectQuery $subQuery) use ($inferredByMembershipQuery) {
                $subQuery
                    ->columns([
                        'group_guid',
                        'user_guid',
                        'created_timestamp',
                        'membership_level',
                    ])
                    ->from('minds_group_membership')
                    ->union($inferredByMembershipQuery)
                    ->alias('a');
            })
            ->groupBy('group_guid', 'user_guid')
            ->limit($limit)
            ->offset($offset);

        if ($groupGuid) {
            $values['group_guid'] = $groupGuid;
            $query->where('group_guid', Operator::EQ, new RawExp(':group_guid'));
        }

        if ($userGuid) {
            $values['user_guid'] = $userGuid;
            $query->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
        }

        if (!$membershipLevel) {
            $membershipLevel = GroupMembershipLevelEnum::MEMBER;
            $query->where('membership_level', Operator::GTE, new RawExp(':membership_level'));
        } elseif ($membershipLevelGte) {
            $query->where('membership_level', Operator::GTE, new RawExp(':membership_level'));
        } else {
            $query->where('membership_level', Operator::EQ, new RawExp(':membership_level'));
        }

        $values['membership_level'] = $membershipLevel->value;

        $pdoStatement = $query->prepare();
        
        $pdoStatement->execute($values);

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $membership = new Membership(
                groupGuid: $row['group_guid'],
                userGuid: $row['user_guid'],
                createdTimestamp: new DateTime($row['created_timestamp']),
                membershipLevel: GroupMembershipLevelEnum::from($row['membership_level'])
            );
            yield $membership;
        }
    }

    /**
     * Returns a count of members for a group, can also return count by membership level
     */
    public function getCount(
        int $groupGuid,
        GroupMembershipLevelEnum $membershipLevel = null,
    ): int {
        $cacheKey = $this->getMemberCountCacheKey($groupGuid, $membershipLevel);

        // Return from cache if possible
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'count' => new RawExp('count(*)')
            ])
            ->from('minds_group_membership')
            ->where('group_guid', Operator::EQ, $groupGuid);

        if (!$membershipLevel) {
            $query->where('membership_level', Operator::GTE, GroupMembershipLevelEnum::MEMBER->value);
        } else {
            $query->where('membership_level', Operator::EQ, $membershipLevel->value);
        }

        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        $count = $rows[0]['count'];

        // Update cache
        $this->cache->set($cacheKey, $count);

        return $count;
    }

    /**
     * Adds a group membership
     */
    public function add(Membership $membership): bool
    {
        $success = $this->mysqlClientWriterHandler->insert()
            ->into('minds_group_membership')
            ->set([
                'group_guid' => $membership->groupGuid,
                'user_guid' => $membership->userGuid,
                'created_timestamp' => $membership->createdTimestamp->format('c'),
                'membership_level' => $membership->membershipLevel->value,
            ])
            ->execute();

        if ($success) {
            // Update the cache
            $this->cache->set($this->getMembershipCacheKey($membership->groupGuid, $membership->userGuid), serialize($membership));
            // Purge the group count cache key for this membership level
            $this->cache->delete($this->getMemberCountCacheKey($membership->groupGuid, $membership->membershipLevel));
            // Purge the group count cache key for all membership levels
            $this->cache->delete($this->getMemberCountCacheKey($membership->groupGuid));
        }

        return $success;
    }

    /**
     * Removes a membership
     */
    public function delete(Membership $membership): bool
    {
        $success = $this->mysqlClientWriterHandler->delete()
            ->from('minds_group_membership')
            ->where('group_guid', Operator::EQ, $membership->groupGuid)
            ->where('user_guid', Operator::EQ, $membership->userGuid)
            ->execute();

        if ($success) {
            // Purge the group membership cache key
            $this->cache->delete($this->getMembershipCacheKey($membership->groupGuid, $membership->userGuid));
            // Purge the group count cache key for each level
            foreach (GroupMembershipLevelEnum::cases() as $membershipLevel) {
                $this->cache->delete($this->getMemberCountCacheKey($membership->groupGuid, $membershipLevel));
            }
            // Purge the group count cache key for all membership levels
            $this->cache->delete($this->getMemberCountCacheKey($membership->groupGuid));
        }

        return $success;
    }

    /**
     * Updates the membership level of a user
     */
    public function updateMembershipLevel(Membership $membership): bool
    {
        $success = $this->mysqlClientWriterHandler->update()
            ->table('minds_group_membership')
            ->set([
                'membership_level' => $membership->membershipLevel->value,
                'updated_timestamp' => (new DateTime())->format('c'),
            ])
            ->where('group_guid', Operator::EQ, $membership->groupGuid)
            ->where('user_guid', Operator::EQ, $membership->userGuid)
            ->execute();

        if ($success) {
            // Update the membership cache key
            $this->cache->set($this->getMembershipCacheKey($membership->groupGuid, $membership->userGuid), serialize($membership));
            // Purge all group count keys
            foreach (GroupMembershipLevelEnum::cases() as $membershipLevel) {
                $this->cache->delete($this->getMemberCountCacheKey($membership->groupGuid, $membershipLevel));
            }
        }
        return $success;
    }

    /**
    * Will return groups that other members, of groups I am in, are also a member of
    * @param string $userGuid
    * @param int $limit
    * @param int $offset
    * @return iterable<int>
    */
    public function getGroupsOfMutualMember(
        int $userGuid,
        int $limit = 3,
        int $offset = 0
    ): iterable {
        $userSharedGroupWithSubquery = $this->mysqlClientReaderHandler->select()
            ->columns([
                'user_guid' => 'b.user_guid',
                'count' => new RawExp('count(*)')
            ])
            ->from('minds_group_membership')
            ->innerJoin(['b'=>'minds_group_membership'], 'minds_group_membership.group_guid', Operator::EQ, 'b.group_guid')
            ->where('minds_group_membership.user_guid', Operator::EQ, new RawExp(':userGuid1'))
            ->where('b.user_guid', Operator::NOT_EQ, new RawExp(':userGuid2'))
            ->groupBy('user_guid')
            ->alias('b');

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'group_guid' => 'minds_group_membership.group_guid',
                'relevance' => new RawExp('count(*)'),
            ])
            ->from(new RawExp('minds_group_membership'))
            // Users that share the same groups
            ->innerJoin(
                new RawExp(rtrim($userSharedGroupWithSubquery->build(), ';')),
                'b.user_guid',
                Operator::EQ,
                'minds_group_membership.user_guid'
            )
            // Exclude groups already a member of
            ->leftJoinRaw(['c' => 'minds_group_membership'], 'c.user_guid = :userGuid3 AND c.group_guid = minds_group_membership.group_guid')
            ->where('c.membership_level', Operator::IS, null)
            ->groupBy('group_guid')
            ->orderBy('relevance desc')
            ->limit($limit)
            ->offset($offset);

        $prepared = $query->prepare();

        $prepared->execute([
            'userGuid1' => $userGuid,
            'userGuid2' => $userGuid,
            'userGuid3' => $userGuid,
        ]);

        foreach ($prepared as $row) {
            yield (int) $row['group_guid'];
        }
    }

    private function getMemberCountCacheKey(int $groupGuid, GroupMembershipLevelEnum $membershipLevel = null): string
    {
        $cacheKey = static::CACHE_KEY_PREFIX . ":$groupGuid:member-count";
        if ($membershipLevel) {
            $cacheKey .= "-" . $membershipLevel->value;
        }
        return $cacheKey;
    }

    private function getMembershipCacheKey(int $groupGuid, int $userGuid): string
    {
        return static::CACHE_KEY_PREFIX . ":$groupGuid:$userGuid";
    }
}
