<?php
namespace Minds\Core\Analytics\UserStates\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared;

class EntityDelegate
{
    /** @var Client */
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Updates the 'last_active' timestamp of users
     * @param array
     * @return void
     */
    public function bulk(array $pendingInserts): void
    {
        foreach ($pendingInserts as $pendingInsert) {
            if (!isset($pendingInsert['doc'])) {
                continue;
            }
            $userGuid = $pendingInsert['doc']['user_guid'];
            $kiteState = $pendingInsert['doc']['state'];
            $kiteRefTs = $pendingInsert['doc']['reference_date'] / 1000;
            $this->saveToDb($userGuid, [ 'kite_state' => $kiteState, 'kite_ref_ts' => $kiteRefTs ]);
        }
    }

    /**
     * @param string
     * @param array
     * @return void
     */
    protected function saveToDb($userGuid, $columns): bool
    {
        foreach ($columns as $column1 => $value) {
            $statement = "UPDATE entities set value = ?
                WHERE column1 = ?
                AND key = ?";
            $values = [ (string) $value, (string) $column1, (string) $userGuid ];
            $prepared = new Prepared\Custom;
            $prepared->query($statement, $values);
            $this->db->request($prepared, true);
        }
        return true;
    }
}
