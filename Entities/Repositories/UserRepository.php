<?php declare(strict_types=1);

namespace Minds\Entities\Repositories;

use Cassandra\Rows;
use Minds\Core;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Data\Cassandra\Client as Cassandra_Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as Cassandra_Prep_Statement;

class UserRepository
{
    public static function getUsersList(Cassandra_Client $cql = null): array
    {
        /** @var abstractCacher $cache */
        $cache = Core\Di\Di::_()->get('Cache');

        $usernames = $cache->get('usernames');
        if (!empty($usernames)) {
            return $usernames;
        }

        $usernames = self::fetchUsernames($cql);

        // Cache for 5 minutes.
        $cache->set('usernames', $usernames, 300);

        return $usernames;
    }

    protected static function fetchUsernames(Cassandra_Client $cql = null): array
    {
        /** @var Cassandra_Client $cql */
        $cql = $cql ?: Core\Di\Di::_()->get('Database\Cassandra\Cql');
        $sql = <<<SQL
SELECT value AS username FROM minds.entities WHERE column1='username' ALLOW FILTERING;
SQL;

        $query = new Cassandra_Prep_Statement();
        $query->query($sql);

        try {
            /** @var Rows $results */
            $results = $cql->request($query);
            if (empty($results)) {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }

        $usernames = [];
        foreach ($results as $row) {
            $usernames[] = $row['username'];
        }

        return $usernames;
    }
}
