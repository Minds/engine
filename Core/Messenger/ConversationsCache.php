<?php
/**
 * Minds messenger conversations cache
 */

namespace Minds\Core\Messenger;

use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Core\Config;

class ConversationsCache
{
    /** @var \Redis */
    private $redis;

    /** @var User */
    private $user;

    /** @var string */
    private $user_guid;

    /** @var Config */
    protected $config;

    public function __construct($redis = null, $config = null)
    {
        $this->redis = $redis ?: new \Redis();
        $this->config = $config ?: Di::_()->get('Config');
        $this->setUser(Session::getLoggedinUser());
    }

    public function setUser($user)
    {
        if ($user instanceof User) {
            $this->user = $user;
            $this->user_guid = $user->guid;
        } elseif (is_string($user)) {
            $this->user_guid = $user;
        }

        return $this;
    }

    public function getGuids($limit = 12, $offset = 0)
    {
        $return = [];

        try {
            $config = $this->config->get('redis');

            // TODO fully move to Redis HA
            $redisHa = ($config['ha']) ?? null;
            if ($redisHa) {
                $master = ($config['master']['host']) ?? null;
                $masterPort = ($config['master']['port']) ?? null;
                
                $this->redis->connect($config['pubsub'] ?: $master ?: '127.0.0.1', $masterPort);
            } else {
                $this->redis->connect($config['pubsub'] ?: $config['master'] ?: '127.0.0.1');
            }
            $return = $this->redis->smembers("object:gathering:conversations:{$this->user_guid}");
        } catch (\Exception $e) {
        }

        return $return;
    }


    public function saveList($conversations)
    {
        try {
            $config = $this->config->get('redis');

            // TODO fully move to Redis HA
            $redisHa = ($config['ha']) ?? null;
            if ($redisHa) {
                $master = ($config['master']['host']) ?? null;
                $masterPort = ($config['master']['port']) ?? null;
                
                $this->redis->connect($config['pubsub'] ?: $master ?: '127.0.0.1', $masterPort);
            } else {
                $this->redis->connect($config['pubsub'] ?: $config['master'] ?: '127.0.0.1');
            }
            $guids = array_map(function ($c) {
                return $c->getGuid();
            }, $conversations);
            array_unshift($guids, "object:gathering:conversations:{$this->user_guid}");
            if (is_array($guids)) {
                call_user_func_array([$this->redis, 'sadd'], $guids);
            }
        } catch (\Exception $e) {
        }
    }
}
