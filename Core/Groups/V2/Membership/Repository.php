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

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'group_guid',
                'user_guid',
                'created_timestamp',
                'membership_level',
                'site_membership_guid',
            ])
            ->from('minds_group_membership')
            ->where('group_guid', Operator::EQ, $groupGuid)
            ->where('user_guid', Operator::EQ, $userGuid)
            ->limit(1);
        
        $stmt = $query->execute();

        if (!$stmt->rowCount()) {
            throw new NotFoundException("User doesn't appear to be in the group");
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $row = $rows[0];
    
        $membership = new Membership(
            groupGuid: $row['group_guid'],
            userGuid: $row['user_guid'],
            createdTimestamp: new DateTime($row['created_timestamp']),
            membershipLevel: GroupMembershipLevelEnum::from($row['membership_level']),
            siteMembershipGuid: $row['site_membership_guid'],
        );

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
        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'group_guid',
                'user_guid',
                'created_timestamp',
                'membership_level',
                'site_membership_guid',
            ])
            ->from('minds_group_membership')
            ->limit($limit)
            ->offset($offset);

        if ($groupGuid) {
            $query->where('group_guid', Operator::EQ, $groupGuid);
        }

        if ($userGuid) {
            $query->where('user_guid', Operator::EQ, $userGuid);
        }

        if (!$membershipLevel) {
            $query->where('membership_level', Operator::GTE, GroupMembershipLevelEnum::MEMBER->value);
        } elseif ($membershipLevelGte) {
            $query->where('membership_level', Operator::GTE, $membershipLevel->value);
        } else {
            $query->where('membership_level', Operator::EQ, $membershipLevel->value);
        }

        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $membership = new Membership(
                groupGuid: $row['group_guid'],
                userGuid: $row['user_guid'],
                createdTimestamp: new DateTime($row['created_timestamp']),
                membershipLevel: GroupMembershipLevelEnum::from($row['membership_level']),
                siteMembershipGuid: $row['site_membership_guid'],
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
                'site_membership_guid' => $membership->siteMembershipGuid,
            ])
            ->onDuplicateKeyUpdate([
                'membership_level' => $membership->membershipLevel->value,
                'site_membership_guid' => $membership->siteMembershipGuid,
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

    /**
     * Returns a list of memberships that should be revoked, because their site membership has lapsed
     * @return iterable<Membership>
     */
    public function getExpiredGroupMemberships(): iterable
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_group_membership')
            ->joinRaw(['msms' => 'minds_site_membership_subscriptions'], 'minds_group_membership.user_guid = msms.user_guid AND minds_group_membership.site_membership_guid = msms.membership_tier_guid')
            ->where('valid_to', Operator::LTE, new RawExp('NOW()'));
    
        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $membership = new Membership(
                groupGuid: $row['group_guid'],
                userGuid: $row['user_guid'],
                createdTimestamp: new DateTime($row['created_timestamp']),
                membershipLevel: GroupMembershipLevelEnum::from($row['membership_level']),
                siteMembershipGuid: $row['site_membership_guid'],
            );
            yield $membership;
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
