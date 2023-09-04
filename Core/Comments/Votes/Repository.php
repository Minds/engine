<?php

/**
 * Minds Comments Votes Repository
 *
 * @author emi
 */

namespace Minds\Core\Comments\Votes;

use Cassandra\Set;
use Cassandra\Type;
use Cassandra\Varint;
use Minds\Core\Comments\Comment;
use Minds\Core\Config\Config;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Votes\Vote;

class Repository
{
    /** @var Client */
    protected $cql;

    /**
     * Repository constructor.
     * @param null $cql
     */
    public function __construct(
        $cql = null,
        private ?Config $config = null
    ) {
        $this->cql = $cql ?: Di::_()->get('Database\Cassandra\Cql');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Inserts a new vote to the votes set
     * @param Vote $vote
     * @return bool
     */
    public function add(Vote $vote)
    {
        /** @var Comment $comment */
        $comment = $vote->getEntity();

        $field = "votes_{$vote->getDirection()}";
        $set = new Set(Type::varint());
        $set->add(new Varint($vote->getActor()->guid));

        $cql = "UPDATE comments
            SET {$field} = {$field} + ?
            WHERE entity_guid = ?
            AND parent_guid_l1 = ?
            AND parent_guid_l2 = ?
            AND parent_guid_l3 = ?
            AND guid = ?";

        // Can't use IF EXISTS if you only have one node in the cluster as it will fail to achieve quorum
        if (!$this->config->get('minds_debug')) {
            $cql .= " IF EXISTS";
        }

        $values = [
            $set,
            new Varint($comment->getEntityGuid()),
            new Varint($comment->getParentGuidL1() ?: 0),
            new Varint($comment->getParentGuidL2() ?: 0),
            new Varint(0),
            new Varint($comment->getGuid()),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return !!$this->cql->request($prepared);
    }

    /**
     * Deletes a vote from the votes set
     * @param Vote $vote
     * @return bool
     */
    public function delete(Vote $vote)
    {
        /** @var Comment $comment */
        $comment = $vote->getEntity();

        $field = "votes_{$vote->getDirection()}";
        $set = new Set(Type::varint());
        $set->add(new Varint($vote->getActor()->guid));

        $cql = "UPDATE comments
            SET {$field} = {$field} - ?
            WHERE entity_guid = ?
            AND parent_guid_l1 = ?
            AND parent_guid_l2 = ?
            AND parent_guid_l3 = ?
            AND guid = ?";

        // Can't use IF EXISTS if you only have one node in the cluster as it will fail to achieve quorum
        if (!$this->config->get('minds_debug')) {
            $cql .= " IF EXISTS";
        }

        $values = [
            $set,
            new Varint($comment->getEntityGuid()),
            new Varint($comment->getParentGuidL1() ?: 0),
            new Varint($comment->getParentGuidL2() ?: 0),
            new Varint(0),
            new Varint($comment->getGuid()),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return !!$this->cql->request($prepared);
    }
}
