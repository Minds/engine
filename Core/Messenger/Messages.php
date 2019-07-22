<?php
/**
 * Minds messenger messages
 */

namespace Minds\Core\Messenger;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Entities;
use Minds\Core\Messenger;
use Minds\Core\Security\ACL;

class Messages
{
    private $indexes;
    private $db;
    private $conversation;
    private $participants = [];
    private $acl;

    public function __construct(
        $db = null,
        $indexes = null,
        $acl = null
    )
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Entities');
        $this->indexes = $indexes ?: Di::_()->get('Database\Cassandra\Indexes');
        $this->acl = $acl ?: ACL::_();
    }

    public function setConversation($conversation)
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getMessages($limit = 12, $offset = "", $finish = "", $idFix = false)
    {
        $this->conversation->setGuid(null); //legacy messages get confused here
        $guid = $this->conversation->getGuid();

        $cassandraOffset = $offset;
        if ($cassandraOffset) {
            $idFix = true;
        }

        if (!$idFix) {
            $cassandraOffset = "1900076691505463296";
        }

        $opts = [
          'limit' => $limit,
          'offset'=> $cassandraOffset,
          'finish'=> $finish,
          'reversed'=> true, 
        ];

        $messages = $this->indexes->get("object:gathering:conversation:$guid", $opts) ?: [];

        $entities = [];

        foreach ($messages as $guid => $json) {
            if ($cassandraOffset < 999999999999999999 && $guid > 999999999999999999) {
                continue;
            }

            $message = json_decode($json, true);
            $entity = new Entities\Message();
            $entity->loadFromArray($message);

            if ($this->acl->read($entity)) {
                $entities[$guid] = $entity;
            }
        }

        // The below code sucks, but it works, kind of...
        // The legacy entities_by_time table uses strings, not integers
        // and cassandra interprets a 9 as being larger than a 10.
        // Here, if we don't get back as many results as we asked for, then we attempt to load
        // all the older posts, which cassandra sees as newer.
        // If this doesn't make sense then speak to @mark or @edgebal

        if ((!$idFix || (int) $cassandraOffset > 999999999999999999) && count($messages) < $limit) {
            $olderEntities = $this->getMessages($limit - count($messages), 999999999999999999, "", true);
            foreach ($olderEntities as $guid => $entity) {
                if ($guid > 999999999999999999) {
                    continue;
                }
                $entities[$guid] = $entity;
            }
        }

        return $entities;
    }
}
