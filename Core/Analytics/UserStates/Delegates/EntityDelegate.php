<?php
namespace Minds\Core\Analytics\UserStates\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Actions;
use Minds\Core\Security\ACL;

class EntityDelegate
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Actions\Save */
    protected $save;

    /** @var ACL */
    protected $acl;

    public function __construct($entitiesBuilder = null, $save = null, $acl = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->save = $save ?? new Actions\Save();
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
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
            $this->save($userGuid, [ 'kite_state' => $kiteState, 'kite_ref_ts' => $kiteRefTs ]);
        }
    }

    /**
     * @param string
     * @param array
     * @return bool
     */
    protected function save($userGuid, $columns): bool
    {
        $entity = $this->entitiesBuilder->single($userGuid);

        if (!$entity) {
            return false;
        }
    
        foreach ($columns as $column1 => $value) {
            $entity->{$column1} = $value;
        }
    
        $ia = $this->acl->setIgnore(true);
        $success = $this->save
            ->setEntity($entity)
            ->save();
        $this->acl->setIgnore($ia);

        return $success;
    }
}
