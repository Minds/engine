<?php

use Minds\Common\IpAddress;

/**
 * ElggUser
 *
 * Representation of a "user" in the system.
 *
 * @package    Elgg.Core
 * @subpackage DataModel.User
 *
 * @property string $name     The display name that the user will be known by in the network
 * @property string $username The short, reference name for the user in the network
 * @property string $email    The email address to which Elgg will send email notifications
 * @property string $language The language preference of the user (ISO 639-1 formatted)
 * @property string $banned   'yes' if the user is banned from the network, 'no' otherwise
 * @property string $admin    'yes' if the user is an administrator of the network, 'no' otherwise
 * @property string $password The hashed password of the user
 * @property string $salt     The salt used to secure the password before hashing
 */
class ElggUser extends ElggEntity
{
    /**
     * Initialise the attributes array.
     * This is vital to distinguish between metadata and base parameters.
     *
     * Place your base parameters here.
     *
     * @return void
     */
    protected function initializeAttributes()
    {
        $this->attributes['type'] = "user";
        $this->attributes['name'] = null;
        $this->attributes['username'] = null;
        $this->attributes['password'] = null;
        $this->attributes['salt'] = null;
        $this->attributes['email'] = null;
        $this->attributes['language'] = null;
        $this->attributes['code'] = null;
        $this->attributes['banned'] = "no";
        $this->attributes['admin'] = 'no';
        $this->attributes['ip'] = (new IpAddress())->get();
        $this->attributes['time_created'] = time();
        $this->attributes['enabled'] = 'yes';
    }

    protected $cache = true;
    public $override_password = false;

    /**
     * Construct a new user entity, optionally from a given id value.
     *
     * @param mixed $guid If an int, load that GUID.
     * 	If an entity table db row then will load the rest of the data.
     *
     * @throws Exception if there was a problem creating the user.
     */
    public function __construct($guid = null, $cache = true)
    {
        $this->cache = $cache;

        $this->initializeAttributes();

        // compatibility for 1.7 api.
        $this->initialise_attributes(false);

        if (!empty($guid)) {
            // Is $guid is a DB entity row
            if ($guid instanceof stdClass) {
                // Load the rest
                if (!$this->load($guid)) {
                    $msg = 'IOException:FailedToLoadGUID ' . get_class() . ' ' . $guid->guid;
                    throw new IOException($msg);
                }
            } elseif (is_numeric($guid) && strlen((string) $guid) >= 18) {
                if (!$this->loadFromGUID($guid)) {
                    throw new IOException('IOException:FailedToLoadGUID ' . get_class() . ' ' . $guid);
                }
            } elseif (is_string($guid)) {
                $this->loadFromLookup($guid);
            } elseif (is_array($guid)) {
                $this->loadFromArray($guid);

            // Is $guid is an ElggUser? Use a copy constructor
            } elseif (is_object($guid)) {
                $this->load($guid);
            }
        }
    }

    /**
     * Load the ElggUser data from the database
     *
     * @param mixed $guid ElggUser GUID or stdClass database row from entity table
     *
     * @return bool
     */
    protected function load($guid)
    {
        foreach ($guid as $k => $v) {
            if ($this->isJson($v)) {
                $v = json_decode($v, true);
            }
            $this->attributes[$k] = $v;
        }

        if ($this->cache) {
            cache_entity($this);
        }

        return true;
    }

    protected function loadFromGUID($guid)
    {
        if (is_numeric($guid) && strlen($guid) < 18) {
            $g = new GUID();
            $guid = $g->migrate($guid);
        }

        if ($this->cache && $cached = retrieve_cached_entity($guid)) {
            $this->load($cached);
            $this->guid = $guid;
            return true;
        }

        /** @var \Minds\Core\Data\Call $db */
        $db = Minds\Core\Di\Di::_()->get('Database\Cassandra\Entities');
        $data = $db->getRow($guid, [ 'limit' => 5000 ]); // This is high to support previous bug in garbage collection in LoginAttempts.php
        $data['guid'] = $guid;
        if ($data) {
            return $this->load($data);
        }

        return false;
    }

    protected function loadFromLookup($string)
    {
        //$cacher = Minds\Core\Data\cache\factory::build();
        //if($guid = $cacher->get("lookup:$string")){
        //    return $this->loadFromGUID(key($guid));
        //}

        /** @var \Minds\Core\Data\lookup $lookup */
        $lookup = Minds\Core\Di\Di::_()->get('Database\Cassandra\Data\Lookup');
        $guid = $lookup->get($string);
        if (!$guid) {
            return false;
        }

        //$cacher->set("lookup:$string", $guid);

        return $this->loadFromGUID(key($guid));
    }

    /**
     * Saves this user to the database.
     *
     * @return bool
     */
    public function save($timebased = true)
    {
        if (!$this->cache) {
            //return false;
        }

        //we do a manual save because we don't want to always update the password
        //@todo find a better less hacky solution
        $new = true;
        if ($this->guid) {
            $new = false;
            elgg_trigger_event('update', $this->type, $this);
        } else {
            $this->guid = (string) new GUID();
            elgg_trigger_event('create', $this->type, $this);
        }

        $db = new Minds\Core\Data\Call('entities');
        $array = $this->toArray();
        if (!$this->override_password && !$new) {
            //error_log('ignoring password save');
            //error_log("new is $new and override is $this->override_password");
            //echo "updating pswd"; exit;
            unset($array['password']);
            unset($array['salt']);
        } else {
            //error_log('allowing password save!');
        }
        
        if (!$this->plus_expires || $this->plus_expires < time()) { //ensure we don't update this field
            unset($array['plus_expires']);
        }

        if (!$this->merchant || !is_array($this->merchant)) {
            unset($array['merchant']); //HACK: only allow updating of merchant if it's an array
        }

        $result = $db->insert($this->guid, $array);

        //now place email and username in index
        $data = [$this->guid => time()];

        $db = new Minds\Core\Data\Call('user_index_to_guid');
        if (!$db->getRow(strtolower($this->username))) {
            $db->insert(strtolower($this->username), $data);
            $db->insert(strtolower($this->email), $data);
            if ($this->phone_number_hash) {
                $db->insert(strtolower($this->phone_number_hash), $data);
            }
        }

        \Minds\Core\Events\Dispatcher::trigger('entities-ops', !$new ? 'update' : 'create', [
            'entityUrn' => $this->getUrn()
        ]);
            
        return $this->guid;
    }

    /**
     * Enable a user
     *
     * @return bool
     */
    public function enable()
    {

        // @note: disabled because $recursive doesn't exist
        //enable all the users objects
        // if($recursive == true){
        //@todo disable the users objects
        // $objects = elgg_get_entities(array('type'=>'object', 'owner_guid'=>$this->guid));
        // foreach($objects as $object){
        //$object->enable();
        // }
        // }

        $db = new Minds\Core\Data\Call('entities_by_time');
        //Remove from the list of unvalidated user
        $db->removeAttributes('user:unvalidated', [$this->guid]);
        //add to the list of unvalidated user
        $db->insert('user', [$this->guid => $this->guid]);

        //Set enabled attribute to 'no'
        $this->enabled = 'yes';
        return (bool) $this->save();
    }

    /**
     * Disable a user
     *
     * @param string $reason    Optional reason
     * @param bool   $recursive Recursively disable all contained entities?
     *
     * @return bool
     */
    public function disable($reason = "", $recursive = true)
    {
        if ($recursive == true) {
            //@todo disable the users objects
            $objects = elgg_get_entities(['type'=>'object', 'owner_guid'=>$this->guid]);
            foreach ($objects as $object) {
                //$object->disable();
            }
        }

        $db = new Minds\Core\Data\Call('entities_by_time');

        //Remove from the list of users
        $db->removeAttributes('user', [$this->guid]);
        //add to the list of unvalidated user
        $db->insert('user:unvalidated', [$this->guid => $this->guid]);

        //Set enabled attribute to 'no'
        $this->enabled = 'no';

        //clear the cache for this
        $this->purgeCache();

        return (bool) $this->save();
    }
    /**
     * User specific override of the entity delete method.
     *
     * @return bool
     */
    public function delete()
    {
        global $USERNAME_TO_GUID_MAP_CACHE, $CODE_TO_GUID_MAP_CACHE;

        // clear cache
        if (isset($USERNAME_TO_GUID_MAP_CACHE[$this->username])) {
            unset($USERNAME_TO_GUID_MAP_CACHE[$this->username]);
        }
        if (isset($CODE_TO_GUID_MAP_CACHE[$this->code])) {
            unset($CODE_TO_GUID_MAP_CACHE[$this->code]);
        }

        if ($this->guid) {
            $db = new Minds\Core\Data\Call('entities_by_time');
            $db->removeAttributes('user', [$this->guid]);
            $db = new Minds\Core\Data\Call('user_index_to_guid');
            $db->removeRow($this->username);
            $db->removeRow($this->email); //@todo we should keep a record of indexes
        }

        $entities = elgg_get_entities(['owner_guid'=>$this->guid, 'limit'=>0]);
        foreach ($entities as $entity) {
            $entity->delete();
        }

        // Delete entity
        return parent::delete();
    }

    /**
     * Ban this user.
     *
     * @param string $reason Optional reason
     *
     * @return bool
     */
    public function ban($reason = "")
    {
        return ban_user($this->guid, $reason);
    }

    /**
     * Unban this user.
     *
     * @return bool
     */
    public function unban()
    {
        return unban_user($this->guid);
    }

    /**
     * Is this user banned or not?
     *
     * @return bool
     */
    public function isBanned()
    {
        return $this->banned == 'yes';
    }

    /**
     * Is this user admin?
     *
     * @return bool
     */
    public function isAdmin()
    {
        $config = Minds\Core\Di\Di::_()->get('Config');

        if ($config->get('development_mode') !== true) {
            $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;

            if (!$ip) {
                return false;
            }

            $whitelist = Minds\Core\Di\Di::_()->get('Config')->get('admin_ip_whitelist');

            if (!$whitelist || !in_array($ip, $whitelist, true)) {
                return false;
            }
        }

        // for backward compatibility we need to pull this directly
        // from the attributes instead of using the magic methods.
        // this can be removed in 1.9
        //var_dump($this->admin);
        //return $this->admin == 'yes';
        return $this->attributes['admin'] == 'yes';
    }

    /**
     * Make the user an admin
     *
     * @return bool
     */
    public function makeAdmin()
    {
        // If already saved, use the standard function.

        if ($this->guid) {
            $this->admin = 'yes';
            $this->save();
        }

        elgg_trigger_event('make_admin', 'user', $this);

        return true;
    }

    /**
     * Remove the admin flag for user
     *
     * @return bool
     */
    public function removeAdmin()
    {
        // If already saved, use the standard function.
        if ($this->guid) {
            $this->admin = 'no';
            $this->attributes['admin'] = 'no';
            return $this->save();
        }
        return false;
    }

    /**
     * Return a count of the users subscriber
     *
     * @return
     */
    public function getSubscribersCount()
    {
        $cacher = \Minds\Core\Data\cache\factory::build();
        if ($cache = $cacher->get("$this->guid:friendsofcount")) {
            return $cache;
        }

        $db = new Minds\Core\Data\Call('friendsof');
        $count = $db->countRow($this->guid);
        if (!$count) {
            $count = 1;
        }
        $cacher->set("$this->guid:friendsofcount", $count);
        return $count;
    }

    /**
     * Return a count of the users subscriptions
     *
     * @return
     */
    public function getSubscriptionsCount()
    {
        $cacher = \Minds\Core\Data\cache\factory::build();
        if ($cache = $cacher->get("$this->guid:friendscount")) {
            return $cache;
        }

        $db = new Minds\Core\Data\Call('friends');
        $count = $db->countRow($this->guid);
        if (!$count) {
            $count = 1;
        }
        $cacher->set("$this->guid:friendscount", $count);
        return $count;
    }

    /**
     * Get a user's owner GUID
     *
     * Returns it's own GUID if the user is not owned.
     *
     * @return int
     */
    public function getOwnerGuid(): string
    {
        if ($this->owner_guid == 0) {
            return $this->guid;
        }

        return $this->owner_guid;
    }

    /**
     * If a user's owner is blank, return its own GUID as the owner
     *
     * @return int User GUID
     * @deprecated 1.8 Use getOwnerGUID()
     */
    public function getOwner()
    {
        elgg_deprecated_notice("ElggUser::getOwner deprecated for ElggUser::getOwnerGUID", 1.8);
        $this->getOwnerGUID();
    }

    // EXPORTABLE INTERFACE ////////////////////////////////////////////////////////////

    /**
     * Return an array of fields which can be exported.
     *
     * @return array
     */
    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), [
            'name',
            'username',
            'language',
            'icontime',
            'legacy_guid',
            'featured_id',
            'banned',
            'ban_reason',
        ]);
    }

    /**
     * Need to catch attempts to make a user an admin.  Remove for 1.9
     *
     * @param string $name  Name
     * @param mixed  $value Value
     *
     * @return bool
     */
    public function __set($name, $value)
    {
        /*	if ($name == 'admin' || $name == 'siteadmin') {
                elgg_deprecated_notice('The admin/siteadmin metadata are not longer used.  Use ElggUser->makeAdmin() and ElggUser->removeAdmin().', 1.7);

                if ($value == 'yes' || $value == '1') {
                    $this->makeAdmin();
                } else {
                    $this->removeAdmin();
                }
            }*/
        return parent::__set($name, $value);
    }

    /**
     * Need to catch attempts to test user for admin.  Remove for 1.9
     *
     * @param string $name Name
     *
     * @return bool
     */
    public function __get($name)
    {
        if ($name == 'admin' || $name == 'siteadmin') {
            elgg_deprecated_notice('The admin/siteadmin metadata are not longer used.  Use ElggUser->isAdmin().', 1.7);
            return $this->isAdmin();
        }

        return parent::__get($name);
    }

    public function purgeCache()
    {
        invalidate_cache_for_entity($this->guid);
    }
}
