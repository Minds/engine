<?php

/**
 * Vote Indexes
 *
 * @author emi
 */

namespace Minds\Core\Votes;

use Minds\Common\Repository\IterableEntity;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities;
use Minds\Entities\User;

class Indexes
{
    /** @var Client */
    protected $cql;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(Client $cql = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->cql = $cql ?: Di::_()->get('Database\Cassandra\Cql');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    public function insert($vote)
    {
        $entity = $vote->getEntity();
        $direction = $vote->getDirection();
        $actor = $vote->getActor();

        $userGuids = $entity->{"thumbs:{$direction}:user_guids"} ?: [];
        $userGuids[] = (string) $actor->guid;

        $userGuids = array_values(array_unique($userGuids));

        $this->setEntityList($entity->guid, $direction, $userGuids);

        // Add to entity based indexes

        $this->addIndex("thumbs:{$direction}:entity:{$entity->guid}", $actor->guid);

        if ($entity->entity_guid) {
            $this->addIndex("thumbs:{$direction}:entity:{$entity->entity_guid}", $actor->guid);
            $this->setEntityList($entity->entity_guid, $direction, $userGuids);
        } elseif (isset($entity->custom_data['guid'])) {
            $this->addIndex("thumbs:{$direction}:entity:{$entity->custom_data['guid']}", $actor->guid);
        }

        // Add to actor based indexes

        $this->addIndex("thumbs:{$direction}:user:{$actor->guid}", $entity->guid);
        $this->addIndex("thumbs:{$direction}:user:{$actor->guid}:{$entity->type}", $entity->guid);

        return true;
    }

    public function remove($vote)
    {
        $entity = $vote->getEntity();
        $direction = $vote->getDirection();
        $actor = $vote->getActor();

        $userGuids = $entity->{"thumbs:{$direction}:user_guids"} ?: [];
        $userGuids = array_diff($userGuids, [ (string) $actor->guid ]);

        $userGuids = array_values($userGuids);

        $this->setEntityList($entity->guid, $direction, $userGuids);

        // Remove from entity based indexes

        $this->removeIndex("thumbs:{$direction}:entity:{$entity->guid}", $actor->guid);

        if ($entity->entity_guid) {
            $this->removeIndex("thumbs:{$direction}:entity:{$entity->entity_guid}", $actor->guid);
            $this->setEntityList($entity->entity_guid, $direction, $userGuids);
        } elseif (isset($entity->custom_data['guid'])) {
            $this->removeIndex("thumbs:{$direction}:entity:{$entity->custom_data['guid']}", $actor->guid);
        }

        // Remove from actor based indexes

        $this->removeIndex("thumbs:{$direction}:user:{$actor->guid}", $entity->guid);
        $this->removeIndex("thumbs:{$direction}:user:{$actor->guid}:{$entity->type}", $entity->guid);

        return true;
    }

    /**
     * Checks for existence
     * @param $entity
     * @param $actor
     * @param $direction
     * @return bool
     * @throws \Exception
     */
    public function exists($vote)
    {
        $entity = $vote->getEntity();
        $actor = $vote->getActor();
        $direction = $vote->getDirection();

        if ($entity instanceof Entities\Activity && $canonicalEntity = $entity->getEntity()) {
            $entity = $canonicalEntity;
        }

        $guids = $entity->{"thumbs:{$direction}:user_guids"} ?: [];
 
        return in_array($actor->guid, $guids, false);
    }

    /**
     * @param int|string $guid
     * @param string $direction
     * @param array $value
     * @return bool|mixed
     */
    protected function setEntityList($guid, $direction, array $value)
    {
        $prepared = new Custom();
        $prepared->query("INSERT INTO entities (key, column1, value) VALUES (?, ?, ?)", [
            (string) $guid,
            "thumbs:{$direction}:user_guids",
            json_encode($value)
        ]);

        return $this->cql->request($prepared);
    }

    /**
     * @param string $index
     * @param int|string $guid
     * @return bool|mixed
     */
    protected function addIndex($index, $guid)
    {
        $prepared = new Custom();
        $prepared->query("INSERT INTO entities_by_time (key, column1, value) VALUES (?, ?, ?)", [
            $index,
            (string) $guid,
            (string) time()
        ]);

        return $this->cql->request($prepared);
    }

    /**
     * @param string $index
     * @param int|string $guid
     * @return bool|mixed
     */
    protected function removeIndex($index, $guid)
    {
        $prepared = new Custom();
        $prepared->query("DELETE FROM entities_by_time WHERE key = ? AND column1 = ?", [
            $index,
            (string) $guid
        ]);

        return $this->cql->request($prepared);
    }

    /**
     * @param VoteListOpts $opts
     * @return iterable
     */
    public function getList(VoteListOpts $opts): iterable
    {
        $entity = $this->entitiesBuilder->single($opts->getEntityGuid());

        if (!$entity) {
            return;
        }

        $prepared = new Custom();
        $prepared->query("SELECT * FROM entities_by_time WHERE key = ?", [
            "thumbs:{$opts->getDirection()}:entity:{$opts->getEntityGuid()}",
        ]);

        $prepared->setOpts([
            'page_size' => $opts->getLimit(),
            'paging_state_token' => base64_decode($opts->getPagingToken(), true)
        ]);

        $rows = $this->cql->request($prepared);
        foreach ($rows as $row) {
            $pagingToken = $rows->pagingStateToken();

            $actor = $this->entitiesBuilder->single($row['column1']);

            if (!$actor instanceof User) {
                continue; // Invalid user, or may not even exist anymore
            }

            $vote = new Vote();
            $vote->setActor($actor);
            $vote->setDirection($opts->getDirection());
            $vote->setEntity($entity);
            yield new IterableEntity($vote, base64_encode($pagingToken));
        }

        return;
    }
}
