<?php
/**
 * Procedural code for creating, loading, and modifying ElggEntity objects.
 *
 * @package Elgg.Core
 * @subpackage DataModel.Entities
 * @link http://docs.elgg.org/DataModel/Entities
 */

/**
 * Cache entities in memory once loaded.
 *
 * @global array $ENTITY_CACHE
 * @access private
 */
global $ENTITY_CACHE;
$ENTITY_CACHE = array();

/**
 * Cache subtypes and related class names.
 *
 * @global array|null $SUBTYPE_CACHE array once populated from DB, initially null
 * @access private
 */
global $SUBTYPE_CACHE;
$SUBTYPE_CACHE = null;

/**
 * Invalidate this class's entry in the cache.
 *
 * @param int $guid The entity guid
 *
 * @return null
 * @access private
 */
function invalidate_cache_for_entity($guid) {
    global $ENTITY_CACHE;

    unset($ENTITY_CACHE[$guid]);

    //remove from XCache
    //@todo remove from all caching apps
    try{
        $cache = new ElggMemcache('new_entity_cache');
        $cache->delete($guid);
    } catch(Exception $e){
    }

    //elgg_get_metadata_cache()->clear($guid);
}

/**
 * Cache an entity.
 *
 * Stores an entity in $ENTITY_CACHE;
 *
 * @param ElggEntity $entity Entity to cache
 *
 * @return null
 * @see retrieve_cached_entity()
 * @see invalidate_cache_for_entity()
 * @access private
 * TODO(evan): Use an ElggCache object
 */
function cache_entity($entity) {
    global $ENTITY_CACHE;

    if (!($entity instanceof ElggEntity)) {
        return;
    }

    // Don't store too many or we'll have memory problems
    // TODO(evan): Pick a less arbitrary limit
    if (count($ENTITY_CACHE) > 256) {
        $random_guid = array_rand($ENTITY_CACHE);

        unset($ENTITY_CACHE[$random_guid]);
    }

    $ENTITY_CACHE[$entity->guid] = $entity;
}

/**
 * Retrieve a entity from the cache.
 *
 * @param int $guid The guid
 *
 * @return ElggEntity|bool false if entity not cached, or not fully loaded
 * @see cache_entity()
 * @see invalidate_cache_for_entity()
 * @access private
 */
function retrieve_cached_entity($guid) {
    global $ENTITY_CACHE;

    if (isset($ENTITY_CACHE[$guid])) {
        if ($ENTITY_CACHE[$guid]->isFullyLoaded()) {
            return $ENTITY_CACHE[$guid];
        }
    }

    return false;
}

/**
 * As retrieve_cached_entity, but returns the result as a stdClass
 * (compatible with load functions that expect a database row.)
 *
 * @param int $guid The guid
 *
 * @return mixed
 * @todo unused
 * @access private
 */
function retrieve_cached_entity_row($guid) {
    $obj = retrieve_cached_entity($guid);
    if ($obj) {
        $tmp = new stdClass;

        foreach ($obj as $k => $v) {
            $tmp->$k = $v;
        }

        return $tmp;
    }

    return false;
}

/**
 * Return the id for a given subtype.
 *
 * ElggEntity objects have a type and a subtype.  Subtypes
 * are defined upon creation and cannot be changed.
 *
 * Plugin authors generally don't need to use this function
 * unless writing their own SQL queries.  Use {@link ElggEntity::getSubtype()}
 * to return the string subtype.
 *
 * @warning {@link ElggEntity::subtype} returns the ID.  You probably want
 * {@link ElggEntity::getSubtype()} instead!
 *
 * @internal Subtypes are stored in the entity_subtypes table.  There is a foreign
 * key in the entities table.
 *
 * @param string $type    Type
 * @param string $subtype Subtype
 *
 * @return int Subtype ID
 * @link http://docs.elgg.org/DataModel/Entities/Subtypes
 * @see get_subtype_from_id()
 * @access private
 */
function get_subtype_id($type, $subtype) {
    global $SUBTYPE_CACHE;

    if (!$subtype) {
        return false;
    }

    if ($SUBTYPE_CACHE === null) {
        _elgg_populate_subtype_cache();
    }

    // use the cache before hitting database
    $result = _elgg_retrieve_cached_subtype($type, $subtype);
    if ($result !== null) {
        return $result->id;
    }

    return false;
}

/**
 * Return string name for a given subtype ID.
 *
 * @param int $subtype_id Subtype ID
 *
 * @return string|false Subtype name, false if subtype not found
 * @link http://docs.elgg.org/DataModel/Entities/Subtypes
 * @see get_subtype_from_id()
 * @access private
 */
function get_subtype_from_id($subtype_id) {
    global $SUBTYPE_CACHE;

    if (!$subtype_id) {
        return false;
    }

    if ($SUBTYPE_CACHE === null) {
        _elgg_populate_subtype_cache();
    }

    if (isset($SUBTYPE_CACHE[$subtype_id])) {
        return $SUBTYPE_CACHE[$subtype_id]->subtype;
    }

    return false;
}

/**
 * Retrieve subtype from the cache.
 *
 * @param string $type
 * @param string $subtype
 * @return stdClass|null
 *
 * @access private
 */
function _elgg_retrieve_cached_subtype($type, $subtype) {
    global $SUBTYPE_CACHE;

    if ($SUBTYPE_CACHE === null) {
        _elgg_populate_subtype_cache();
    }

    foreach ($SUBTYPE_CACHE as $obj) {
        if ($obj->type === $type && $obj->subtype === $subtype) {
            return $obj;
        }
    }
    return null;
}

/**
 * Fetch all suptypes from DB to local cache.
 *
 * @access private
 */
function _elgg_populate_subtype_cache() {
    global $CONFIG, $SUBTYPE_CACHE;

    return;
}

/**
 * Return the class name for a registered type and subtype.
 *
 * Entities can be registered to always be loaded as a certain class
 * with add_subtype() or update_subtype(). This function returns the class
 * name if found and NULL if not.
 *
 * @param string $type    The type
 * @param string $subtype The subtype
 *
 * @return string|null a class name or null
 * @see get_subtype_from_id()
 * @see get_subtype_class_from_id()
 * @access private
 */
function get_subtype_class($type, $subtype) {
    global $SUBTYPE_CACHE;

    if ($SUBTYPE_CACHE === null) {
        _elgg_populate_subtype_cache();
    }

    // use the cache before going to the database
    $obj = _elgg_retrieve_cached_subtype($type, $subtype);
    if ($obj) {
        return $obj->class;
    }

    return null;
}

/**
 * Returns the class name for a subtype id.
 *
 * @param int $subtype_id The subtype id
 *
 * @return string|null
 * @see get_subtype_class()
 * @see get_subtype_from_id()
 * @access private
 */
function get_subtype_class_from_id($subtype_id) {
    global $SUBTYPE_CACHE;

    if (!$subtype_id) {
        return null;
    }

    if ($SUBTYPE_CACHE === null) {
        _elgg_populate_subtype_cache();
    }

    if (isset($SUBTYPE_CACHE[$subtype_id])) {
        return $SUBTYPE_CACHE[$subtype_id]->class;
    }

    return null;
}

/**
 * Register ElggEntities with a certain type and subtype to be loaded as a specific class.
 *
 * By default entities are loaded as one of the 4 parent objects: site, user, object, or group.
 * If you subclass any of these you can register the classname with add_subtype() so
 * it will be loaded as that class automatically when retrieved from the database with
 * {@link get_entity()}.
 *
 * @warning This function cannot be used to change the class for a type-subtype pair.
 * Use update_subtype() for that.
 *
 * @param string $type    The type you're subtyping (site, user, object, or group)
 * @param string $subtype The subtype
 * @param string $class   Optional class name for the object
 *
 * @return int
 * @link http://docs.elgg.org/Tutorials/Subclasses
 * @link http://docs.elgg.org/DataModel/Entities
 * @see update_subtype()
 * @see remove_subtype()
 * @see get_entity()
 */
function add_subtype($type, $subtype, $class = "") {
    global $SUBTYPE_CACHE;
    $cache_obj = (object) array(
            'type' => $type,
            'subtype' => $subtype,
            'class' => $class,
        );

    if(class_exists($class)){
        $cache_obj->id = $subtype;
        $SUBTYPE_CACHE[$subtype] = $cache_obj;
        return $cache_obj;
    }
    return;
}

/**
 * Removes a registered ElggEntity type, subtype, and classname.
 *
 * @warning You do not want to use this function. If you want to unregister
 * a class for a subtype, use update_subtype(). Using this function will
 * permanently orphan all the objects created with the specified subtype.
 *
 * @param string $type    Type
 * @param string $subtype Subtype
 *
 * @return bool
 * @see add_subtype()
 * @see update_subtype()
 */
function remove_subtype($type, $subtype) {
    return;
}
/**
 * Update a registered ElggEntity type, subtype, and class name
 *
 * @param string $type    Type
 * @param string $subtype Subtype
 * @param string $class   Class name to use when loading this entity
 *
 * @return bool
 */
function update_subtype($type, $subtype, $class = '') {
    return;
}

/**
 * Update an entity in the database.
 *
 * There are 4 basic entity types: site, user, object, and group.
 * All entities are split between two tables: the entities table and their type table.
 *
 * @warning Plugin authors should never call this directly. Use ->save() instead.
 *
 * @param int $guid           The guid of the entity to update
 * @param int $owner_guid     The new owner guid
 * @param int $access_id      The new access id
 * @param int $container_guid The new container guid
 * @param int $time_created   The time creation timestamp
 *
 * @return bool
 * @link http://docs.elgg.org/DataModel/Entities
 * @access private
 */
function update_entity($guid, $owner_guid, $access_id, $container_guid = null, $time_created = null) {
    global $CONFIG, $ENTITY_CACHE;

    $guid = (int)$guid;
    $owner_guid = (int)$owner_guid;
    $access_id = (int)$access_id;
    $container_guid = (int) $container_guid;
    if (is_null($container_guid)) {
        $container_guid = $owner_guid;
    }
    $time = time();

    $entity = get_entity($guid);

    if ($time_created == null) {
        $time_created = $entity->time_created;
    } else {
        $time_created = (int) $time_created;
    }

    if ($entity && $entity->canEdit()) {
        if (elgg_trigger_event('update', $entity->type, $entity)) {
            $ret = update_data("UPDATE {$CONFIG->dbprefix}entities
                set owner_guid='$owner_guid', access_id='$access_id',
                container_guid='$container_guid', time_created='$time_created',
                time_updated='$time' WHERE guid=$guid");

            if ($entity instanceof ElggObject) {
                update_river_access_by_object($guid, $access_id);
            }

            // If memcache is available then delete this entry from the cache
            if (is_memcache_available()) {
                $memcache = new ElggMemcache('new_entity_cache');
                $memcache->delete($guid);
            }

            // Handle cases where there was no error BUT no rows were updated!
            if ($ret === false) {
                return false;
            }

            return true;
        }
    }
}

/**
 * Determine if a given user can write to an entity container.
 *
 * An entity can be a container for any other entity by setting the
 * container_guid.  container_guid can differ from owner_guid.
 *
 * A plugin hook container_permissions_check:$entity_type is emitted to allow granular
 * access controls in plugins.
 *
 * @param int    $user_guid      The user guid, or 0 for logged in user
 * @param int    $container_guid The container, or 0 for the current page owner.
 * @param string $type           The type of entity we're looking to write
 * @param string $subtype        The subtype of the entity we're looking to write
 *
 * @return bool
 * @link http://docs.elgg.org/DataModel/Containers
 */
function can_write_to_container($user_guid = 0, $container_guid = 0, $type = 'all', $subtype = 'all') {
    $user_guid = (int)$user_guid;
    $user = get_entity($user_guid, 'user');
    if (!$user) {
        $user = elgg_get_logged_in_user_entity();
    }

    $container_guid = (int)$container_guid;
    if (!$container_guid) {
        $container_guid = elgg_get_page_owner_guid();
    }

    $return = false;

    if (!$container_guid) {
        $return = true;
    }

    $container = get_entity($container_guid, 'user');
    if(!$container){
        $container = get_entity($container_guid, 'group');
    }

    if ($container) {
        // If the user can edit the container, they can also write to it
        if ($container->canEdit($user_guid)) {
            $return = true;
        }

        // If still not approved, see if the user is a member of the group
        // @todo this should be moved to the groups plugin/library
        if (!$return && $user && $container instanceof ElggGroup) {
            /* @var ElggGroup $container */
            if ($container->isMember($user)) {
                $return = true;
            }
        }
    }

    // See if anyone else has anything to say
    return elgg_trigger_plugin_hook(
            'container_permissions_check',
            $type,
            array(
                'container' => $container,
                'user' => $user,
                'subtype' => $subtype
            ),
            $return);
}

/**
 * Add a perma link to the entity
 */
function create_entity_event_hook($event, $object_type, $object) {
    $url = $object->getURL();
    if($url){
        $object->perma_url = $url;
    }
}
elgg_register_event_handler('create', 'object', 'create_entity_event_hook');

/**
 * @deprecated
 */
function create_entity($object = NULL, $timebased = true) {
    return false;
}

/**
 * @deprecated
 */

function get_entity_as_row($guid, $type) {
    return false;
}

/**
 * Create an Elgg* object from a given entity row.
 *
 * Handles loading all tables into the correct class.
 *
 * @param stdClass $row The row of the entry in the entities table.
 *
 * @return ElggEntity|false
 * @link http://docs.elgg.org/DataModel/Entities
 * @see get_entity_as_row()
 * @see add_subtype()
 * @see get_entity()
 * @access private
 *
 * @throws ClassException|InstallationException
 */
function entity_row_to_elggstar($row, $cache = true) {

    return \Minds\Core\Entities::build($row, $cache);

    if (!($row instanceof stdClass)) {
        return $row;
    }

    if ((!isset($row->guid))) {
        return $row;
    }

    $new_entity = false;

    $memcache = false;
    // Create a memcache cache if we can
    if (is_memcache_available()) {
        $memcache = new ElggMemcache('new_entity_cache');
        //$new_entity = $memcache->load($row->guid);
    }

    if ($new_entity) {
        return $new_entity;
    }

    if($new_entity = elgg_trigger_plugin_hook('entities_class_loader', 'all', $row))
        return $new_entity;

    // load class for entity if one is registered
    if(isset($row->subtype)){
        $classname = get_subtype_class_from_id($row->subtype);
        if ($classname != "") {
            if (class_exists($classname)) {
                $new_entity = new $classname($row);

                if (!($new_entity instanceof ElggEntity)) {
                    $msg = elgg_echo('ClassException:ClassnameNotClass', array($classname, 'ElggEntity'));
                    throw new ClassException($msg);
                }
            } else {
                error_log(elgg_echo('ClassNotFoundException:MissingClass', array($classname)));
            }
        }
    }

    if (!$new_entity) {
        //@todo Make this into a function
        switch ($row->type) {
            case 'object' :
                $new_entity = new ElggObject($row);
                break;
            case 'user' :
                $new_entity = new ElggUser($row, $cache);
                break;
            case 'group' :
                $new_entity = new ElggGroup($row);
                break;
            case 'site' :
                $new_entity = new ElggSite($row);
                break;
            case 'plugin' :
                $new_entity = new ElggPlugin($row);
                break;
            case 'widget' :
                $new_entity = new ElggWidget($row);
                        break;
            case 'notification' :
                $new_entity = new \Minds\Entities\Notification($row);
                break;
            default:
                $msg = elgg_echo('InstallationException:TypeNotSupported', array($row->type));
                throw new InstallationException($msg);
        }
    }

    // Cache entity if we have a cache available
    if (($memcache) && ($new_entity)) {
        $memcache->save($new_entity->guid, $new_entity);
    }
    //cache_entity($new_entity);
    return $new_entity;
}

/**
 * Loads and returns an entity object from a guid.
 *
 * @param int $guid The GUID of the entity
 *
 * @return ElggEntity The correct Elgg or custom object based upon entity type and subtype
 * @link http://docs.elgg.org/DataModel/Entities
 */
function get_entity($guid, $type = 'object') {

    if(!$guid || $guid == 0){
        return;
    }

    //legacy style guid?
    if((strlen($guid) < 18) && ($type!='site') && ($type!='plugin') && ($type!='api_user')){
        $newguid = new GUID();
        $guid = $newguid->migrate($guid);
    }

    // Check local cache first
    $new_entity = retrieve_cached_entity($guid);
    if ($new_entity)
        return $new_entity;

    $cached_entity = null;
    if (is_memcache_available()) {
        $memcache = new ElggMemcache('new_entity_cache');
        $cached_entity = $memcache->load($guid);
    }

    if ($cached_entity) {
        error_log("loaded $guid from memcached");
        // @todo use ACL and cached entity access_id to determine if user can see it
        return $cached_entity;
    }

    $db = new Minds\Core\Data\Call('entities');
    $row = $db->getRow($guid);
    if(!$row){
        return false;
    }
    $row['guid'] = $guid;
    if(!isset($row['type'])){
        $row['type'] = $type;
    }
    $new_entity = entity_row_to_elggstar($db->createObject($row));

    //check access permissions
    if(!Minds\Core\Security\ACL::_()->read($new_entity)){
        return false; //@todo return error too
    }

    if ($new_entity) {
         if (is_memcache_available()) {
          //             $memcache = new ElggMemcache('new_entity_cache');
        //          $memcache->save($guid, $new_entity);
            }
        cache_entity($new_entity);
    }
    return $new_entity;
}

/**
 * @deprecated;
 */
function elgg_entity_exists($guid) {
    return true;
}

/**
 * Returns an array of entities with optional filtering.
 *
 * Entities are the basic unit of storage in Elgg.  This function
 * provides the simplest way to get an array of entities.  There
 * are many options available that can be passed to filter
 * what sorts of entities are returned.
 *
 * @tip To output formatted strings of entities, use {@link elgg_list_entities()} and
 * its cousins.
 *
 * @tip Plural arguments can be written as singular if only specifying a
 * single element.  ('type' => 'object' vs 'types' => array('object')).
 *
 * @param array $options Array in format:
 *
 *  types => NULL|STR entity type (type IN ('type1', 'type2')
 *           Joined with subtypes by AND. See below)
 *
 *  subtypes => NULL|STR entity subtype (SQL: subtype IN ('subtype1', 'subtype2))
 *              Use ELGG_ENTITIES_NO_VALUE for no subtype.
 *
 *  type_subtype_pairs => NULL|ARR (array('type' => 'subtype'))
 *                        (type = '$type' AND subtype = '$subtype') pairs
 *
 *  guids => NULL|ARR Array of entity guids
 *
 *  owner_guids => NULL|ARR Array of owner guids
 *
 *  container_guids => NULL|ARR Array of container_guids
 *
 *  site_guids => NULL (current_site)|ARR Array of site_guid
 *
 *  order_by => NULL (time_created desc)|STR SQL order by clause
 *
 *  reverse_order_by => BOOL Reverse the default order by clause
 *
 *  limit => NULL (10)|INT SQL limit clause (0 means no limit)
 *
 *  offset => NULL (0)|INT SQL offset clause
 *
 *  created_time_lower => NULL|INT Created time lower boundary in epoch time
 *
 *  created_time_upper => NULL|INT Created time upper boundary in epoch time
 *
 *  modified_time_lower => NULL|INT Modified time lower boundary in epoch time
 *
 *  modified_time_upper => NULL|INT Modified time upper boundary in epoch time
 *
 *  count => TRUE|FALSE return a count instead of entities
 *
 *  wheres => array() Additional where clauses to AND together
 *
 *  joins => array() Additional joins
 *
 *  callback => string A callback function to pass each row through
 *
 * @return mixed If count, int. If not count, array. false on errors.
 * @since 1.7.0
 * @see elgg_get_entities_from_metadata()
 * @see elgg_get_entities_from_relationship()
 * @see elgg_get_entities_from_access_id()
 * @see elgg_get_entities_from_annotations()
 * @see elgg_list_entities()
 * @link http://docs.elgg.org/DataModel/Entities/Getters
 */
function elgg_get_entities(array $options = array()) {
    global $CONFIG;

    $entities = null;

    $defaults = array(
        'types'                 =>  array('object'),
        'subtypes'              =>  ELGG_ENTITIES_ANY_VALUE,

        'timebased' => true,

        'newest_first'  => true,

        'guids'                 =>  ELGG_ENTITIES_ANY_VALUE,
        'owner_guids'           =>  ELGG_ENTITIES_ANY_VALUE,
        'network'               => NULL,
        'container_guids'       =>  ELGG_ENTITIES_ANY_VALUE,
        'site_guids'            =>  $CONFIG->site_guid,

        'limit'                 =>  10,
        'offset'                => "",
        'count'                 =>  FALSE,

        'attrs'             => array(),

        'callback'              => 'entity_row_to_elggstar',
    );

    $options = array_merge($defaults, $options);

    $singulars = array('type', 'subtype', 'guid', 'owner_guid', 'container_guid', 'site_guid');
    $options = elgg_normalise_plural_options_array($options, $singulars);

    $attrs = $options['attrs'];
    if($subtypes = $options['subtypes']){
        $attrs['subtype'] = $subtypes[0];
    }

    if($owner_guid = $options['owner_guids']){
        $attrs['owner_guid'] = $owner_guid[0];
    }

    if($container_guid = $options['container_guids']){
        $attrs['container_guid'] = $container_guid[0];
    }

    if($options['limit'] == false || $options['limit'] == 0){
        //unset($options['limit']);
        $options['limit'] = 999999;
    }

    //hack to make ajax lists not show duplicates
    if(elgg_get_viewtype() == 'json' && $options['offset'] > 0){
        $options['limit']++;
    }

    $type = $options['types'] ? $options['types'][0] : "object";

        try{
            //1. If guids are passed then return them all. Subtypes and other values don't matter in this case
            if($options['guids']){

                $db = new Minds\Core\Data\Call('entities');
                $rows = $db->getRows($options['guids']);

            } else{
                if($options['timebased']){
                    $namespace = isset($attrs['namespace']) ? $attrs['namespace'] : null;
                    if(!$namespace){
                        $namespace = $type;
                        if($subtypes){
                            $namespace .= ':'. $subtypes[0]; //change to subtype
                        }
                        if($owner_guid = $options['owner_guids'][0]){
                            $namespace .= ':user:'. $owner_guid;
                        }
                        if($container_guid = $options['container_guids'][0]){
                            $namespace .= ':container:'. $container_guid;
                        }
                        if($network = $options['network']){
                            $namespace .= ':network:'.$network;
                        }
                    }
                    if(!$options['count']){
                        $db = new Minds\Core\Data\Call('entities_by_time');
                        $guids = $db->getRow($namespace, array('offset'=>$options['offset'], 'limit'=>$options['limit'], 'reversed'=> $options['newest_first']));

                        if(!$guids){
                            return false;
                        }

                        if(isset($guids[$options['offset']])){
                        //  unset($guids[$options['offset']]); //prevents looping...
                        }

                        $db = new Minds\Core\Data\Call('entities');
                        $rows = $db->getRows(array_keys($guids));
                        if(!$rows){
                            return false;
                        }

                    } else {
                        $db = new Minds\Core\Data\Call('entities_by_time');
                        $count = $db->countRow($namespace);
                        return $count;
                    }
                } else {
                    if($attrs){
                        $db = new Minds\Core\Data\Call('entities');
                        $rows = $db->getByIndex($attrs, $options['offset'], $options['limit']);
                    } else {
                        $db = new Minds\Core\Data\Call('entities');
                        $rows = $db->get($offset,"", $limit);
                    }
                }
            }
            if($rows){
                foreach($rows as $guid=>$row){
                    //convert array to std class
                    $newrow = new stdClass;
                    $newrow->guid = $guid;
                    if(!isset($row->type) || !$row->type){
                        $newrow->type = $type;
                    }
                    foreach($row as $k=>$v){
                        $newrow->$k = $v;
                    }

                    $entity = entity_row_to_elggstar($newrow);
                    if(Minds\Core\Security\ACL::_()->read($entity))
                        $entities[] = $entity;
                }
            }
        } catch(Exception $e){
            //var_dump($e);
            //@todo report error to admin
        }
        return $entities;

}

/**
 * @deprecated
 */
function _elgg_fetch_entities_from_sql($sql) {
    return false;
}

/**
 * @deprecated
 */
function elgg_get_entity_type_subtype_where_sql($table, $types, $subtypes, $pairs) {
    return false;
}

/**
 * @deprecated
 */
function elgg_get_guid_based_where_sql($column, $guids) {
    return false;
}

/**
 * @deprecated
 */
function elgg_get_entity_time_where_sql($table, $time_created_upper = NULL,
$time_created_lower = NULL, $time_updated_upper = NULL, $time_updated_lower = NULL) {

    return false;
}

/**
 * Returns a string of parsed entities.
 *
 * Displays list of entities with formatting specified
 * by the entity view.
 *
 * @tip Pagination is handled automatically.
 *
 * @internal This also provides the views for elgg_view_annotation().
 *
 * @param array $options Any options from $getter options plus:
 *  full_view => BOOL Display full view entities
 *  list_type => STR 'list' or 'gallery'
 *  list_type_toggle => BOOL Display gallery / list switch
 *  pagination => BOOL Display pagination links
 *
 * @param mixed $getter  The entity getter function to use to fetch the entities
 * @param mixed $viewer  The function to use to view the entity list.
 *
 * @return string
 * @since 1.7
 * @see elgg_get_entities()
 * @see elgg_view_entity_list()
 * @link http://docs.elgg.org/Entities/Output
 */
function elgg_list_entities(array $options = array(), $getter = 'elgg_get_entities',
    $viewer = 'elgg_view_entity_list') {

    global $autofeed;
    $autofeed = true;

    $defaults = array(
        'offset' => get_input('offset', 0),
        'limit' => (int) max(get_input('limit', 10), 0),
        'full_view' => TRUE,
        'list_type_toggle' => FALSE,
        'pagination' => TRUE,
    );

    $options = array_merge($defaults, $options);

    //backwards compatibility
    if (isset($options['view_type_toggle'])) {
        $options['list_type_toggle'] = $options['view_type_toggle'];
    }

    if(is_bool($options['count'])){
        $options['count'] = TRUE;
        $count = $getter($options);

        $options['count'] = FALSE;
        $entities = $getter($options);
        $options['count'] = $count;
    } else {
        $entities = $getter($options);
    }

    if(!$entities){
        $entities = array();
    }
    return $viewer($entities, $options);
}

/**
 * @deprecated
 */
function get_entity_dates($type = '', $subtype = '', $container_guid = 0, $site_guid = 0,
$order_by = 'time_created') {
    return false;
}

/**
 * @deprecated
 */
function disable_entity($guid, $reason = "", $recursive = true) {
    return false;
}

/**
 * @deprecated
 */
function enable_entity($guid, $recursive = true) {
    return false;
}

/**
 * Delete an entity.
 * @deprecated
 */
function delete_entity($guid, $type = 'object',$recursive = true) {
    return false;
}

/**
 * Exports attributes generated on the fly (volatile) about an entity.
 *
 * @param string $hook        volatile
 * @param string $entity_type metadata
 * @param string $returnvalue Return value from previous hook
 * @param array  $params      The parameters, passed 'guid' and 'varname'
 *
 * @return ElggMetadata|null
 * @elgg_plugin_hook_handler volatile metadata
 * @todo investigate more.
 * @access private
 * @todo document
 */
function volatile_data_export_plugin_hook($hook, $entity_type, $returnvalue, $params) {
    $guid = (int)$params['guid'];
    $variable_name = sanitise_string($params['varname']);

    if (($hook == 'volatile') && ($entity_type == 'metadata')) {
        if (($guid) && ($variable_name)) {
            switch ($variable_name) {
                case 'renderedentity' :
                    elgg_set_viewtype('default');
                    $view = elgg_view_entity(get_entity($guid));
                    elgg_set_viewtype();

                    $tmp = new ElggMetadata();
                    $tmp->type = 'volatile';
                    $tmp->name = 'renderedentity';
                    $tmp->value = $view;
                    $tmp->entity_guid = $guid;

                    return $tmp;

                break;
            }
        }
    }
}

/**
 * Exports all attributes of an entity.
 *
 * @warning Only exports fields in the entity and entity type tables.
 *
 * @param string $hook        export
 * @param string $entity_type all
 * @param mixed  $returnvalue Previous hook return value
 * @param array  $params      Parameters
 *
 * @elgg_event_handler export all
 * @return mixed
 * @access private
 *
 * @throws InvalidParameterException|InvalidClassException
 */
function export_entity_plugin_hook($hook, $entity_type, $returnvalue, $params) {
    // Sanity check values
    if ((!is_array($params)) && (!isset($params['guid']))) {
        throw new InvalidParameterException(elgg_echo('InvalidParameterException:GUIDNotForExport'));
    }

    if (!is_array($returnvalue)) {
        throw new InvalidParameterException(elgg_echo('InvalidParameterException:NonArrayReturnValue'));
    }

    $guid = (int)$params['guid'];

    // Get the entity
    $entity = get_entity($guid);
    if (!($entity instanceof ElggEntity)) {
        $msg = elgg_echo('InvalidClassException:NotValidElggStar', array($guid, get_class()));
        throw new InvalidClassException($msg);
    }

    $export = $entity->export();

    if (is_array($export)) {
        foreach ($export as $e) {
            $returnvalue[] = $e;
        }
    } else {
        $returnvalue[] = $export;
    }

    return $returnvalue;
}

/**
 * Utility function used by import_entity_plugin_hook() to
 * process an ODDEntity into an unsaved ElggEntity.
 *
 * @param ODDEntity $element The OpenDD element
 *
 * @return ElggEntity the unsaved entity which should be populated by items.
 * @todo Remove this.
 * @access private
 *
 * @throws ClassException|InstallationException|ImportException
 */
function oddentity_to_elggentity(ODDEntity $element) {
    $class = $element->getAttribute('class');
    $subclass = $element->getAttribute('subclass');

    // See if we already have imported this uuid
    $tmp = get_entity_from_uuid($element->getAttribute('uuid'));

    if (!$tmp) {
        // Construct new class with owner from session
        $classname = get_subtype_class($class, $subclass);
        if ($classname) {
            if (class_exists($classname)) {
                $tmp = new $classname();

                if (!($tmp instanceof ElggEntity)) {
                    $msg = elgg_echo('ClassException:ClassnameNotClass', array($classname, get_class()));
                    throw new ClassException($msg);
                }
            } else {
                error_log(elgg_echo('ClassNotFoundException:MissingClass', array($classname)));
            }
        } else {
            switch ($class) {
                case 'object' :
                    $tmp = new ElggObject($row);
                    break;
                case 'user' :
                    $tmp = new ElggUser($row);
                    break;
                case 'group' :
                    $tmp = new ElggGroup($row);
                    break;
                case 'site' :
                    $tmp = new ElggSite($row);
                    break;
                default:
                    $msg = elgg_echo('InstallationException:TypeNotSupported', array($class));
                    throw new InstallationException($msg);
            }
        }
    }

    if ($tmp) {
        if (!$tmp->import($element)) {
            $msg = elgg_echo('ImportException:ImportFailed', array($element->getAttribute('uuid')));
            throw new ImportException($msg);
        }

        return $tmp;
    }

    return NULL;
}

/**
 * Import an entity.
 *
 * This function checks the passed XML doc (as array) to see if it is
 * a user, if so it constructs a new elgg user and returns "true"
 * to inform the importer that it's been handled.
 *
 * @param string $hook        import
 * @param string $entity_type all
 * @param mixed  $returnvalue Value from previous hook
 * @param mixed  $params      Array of params
 *
 * @return mixed
 * @elgg_plugin_hook_handler import all
 * @todo document
 * @access private
 *
 * @throws ImportException
 */
function import_entity_plugin_hook($hook, $entity_type, $returnvalue, $params) {
    $element = $params['element'];

    $tmp = null;

    if ($element instanceof ODDEntity) {
        $tmp = oddentity_to_elggentity($element);

        if ($tmp) {
            // Make sure its saved
            if (!$tmp->save()) {
                $msg = elgg_echo('ImportException:ProblemSaving', array($element->getAttribute('uuid')));
                throw new ImportException($msg);
            }

            // Belts and braces
            if (!$tmp->guid) {
                throw new ImportException(elgg_echo('ImportException:NoGUID'));
            }

            // We have saved, so now tag
            add_uuid_to_guid($tmp->guid, $element->getAttribute('uuid'));

            return $tmp;
        }
    }
}

/**
 * Returns if $user_guid is able to edit $entity_guid.
 *
 * @tip Can be overridden by by registering for the permissions_check
 * plugin hook.
 *
 * @warning If a $user_guid is not passed it will default to the logged in user.
 *
 * @tip Use ElggEntity::canEdit() instead.
 *
 * @param int $entity_guid The GUID of the entity
 * @param int $user_guid   The GUID of the user
 *
 * @return bool
 * @link http://docs.elgg.org/Entities/AccessControl
 */
function can_edit_entity($entity_guid, $user_guid = 0) {
    $user = get_entity($user_guid, 'user');
    if (!$user) {
        $user = elgg_get_logged_in_user_entity();
    }

    $return = false;
    if ($entity = get_entity($entity_guid, 'object')) {

        // Test user if possible - should default to false unless a plugin hook says otherwise
        if ($user) {
            if ($entity->getOwnerGUID() == $user->getGUID()) {
                $return = true;
            }
            if ($entity->container_guid == $user->getGUID()) {
                $return = true;
            }
            if ($entity->type == "user" && $entity->getGUID() == $user->getGUID()) {
                $return = true;
            }
            //@todo fix issue with container id's being set wrongly. Also don't call the database if we don't need to!
            $container_guid = $entity->container_guid == 111111111111111110 ? 0 : $entity->container_guid;
            if(!$return){
                if ($container_entity = get_entity($container_guid, 'user')) {
                    if ($container_entity->canEdit($user->getGUID())) {
                        $return = true;
                    }
                }
            }
        }
    }

    return elgg_trigger_plugin_hook('permissions_check', $entity->type,
            array('entity' => $entity, 'user' => $user), $return);
}

/**
 * Returns if $user_guid can edit the metadata on $entity_guid.
 *
 * @tip Can be overridden by by registering for the permissions_check:metadata
 * plugin hook.
 *
 * @warning If a $user_guid isn't specified, the currently logged in user is used.
 *
 * @param int          $entity_guid The GUID of the entity
 * @param int          $user_guid   The GUID of the user
 * @param ElggMetadata $metadata    The metadata to specifically check (if any; default null)
 *
 * @return bool
 * @see elgg_register_plugin_hook_handler()
 */
function can_edit_entity_metadata($entity_guid, $user_guid = 0, $metadata = null) {
    if ($entity = get_entity($entity_guid)) {

        $return = null;

        if ($metadata->owner_guid == 0) {
            $return = true;
        }
        if (is_null($return)) {
            $return = can_edit_entity($entity_guid, $user_guid);
        }

        if ($user_guid) {
            $user = get_entity($user_guid);
        } else {
            $user = elgg_get_logged_in_user_entity();
        }

        $params = array('entity' => $entity, 'user' => $user, 'metadata' => $metadata);
        $return = elgg_trigger_plugin_hook('permissions_check:metadata', $entity->type, $params, $return);
        return $return;
    } else {
        return false;
    }
}

/**
 * Returns the URL for an entity.
 *
 * @tip Can be overridden with {@link register_entity_url_handler()}.
 *
 * @param int $entity_guid The GUID of the entity
 *
 * @return string The URL of the entity
 * @see register_entity_url_handler()
 */
function get_entity_url($entity_guid, $type) {
    global $CONFIG;

    if ($entity = get_entity($entity_guid, $type)) {
        $url = "";

        if($entity->legacy_guid){
            $entity_guid = $entity->legacy_guid;
        }

        if (isset($CONFIG->entity_url_handler[$entity->getType()][$entity->getSubType()])) {
            $function = $CONFIG->entity_url_handler[$entity->getType()][$entity->getSubType()];
            if (is_callable($function)) {
                $url = call_user_func($function, $entity);
            }
        } elseif (isset($CONFIG->entity_url_handler[$entity->getType()]['all'])) {
            $function = $CONFIG->entity_url_handler[$entity->getType()]['all'];
            if (is_callable($function)) {
                $url = call_user_func($function, $entity);
            }
        } elseif (isset($CONFIG->entity_url_handler['all']['all'])) {
            $function = $CONFIG->entity_url_handler['all']['all'];
            if (is_callable($function)) {
                $url = call_user_func($function, $entity);
            }
        }

        if ($url == "") {
            $url = "view/" . $entity_guid;
        }

        return elgg_normalize_url($url);
    }

    return false;
}

/**
 * Sets the URL handler for a particular entity type and subtype
 *
 * @param string $entity_type    The entity type
 * @param string $entity_subtype The entity subtype
 * @param string $function_name  The function to register
 *
 * @return bool Depending on success
 * @see get_entity_url()
 * @see ElggEntity::getURL()
 * @since 1.8.0
 */
function elgg_register_entity_url_handler($entity_type, $entity_subtype, $function_name) {
    global $CONFIG;

    if (!is_callable($function_name, true)) {
        return false;
    }

    if (!isset($CONFIG->entity_url_handler)) {
        $CONFIG->entity_url_handler = array();
    }

    if (!isset($CONFIG->entity_url_handler[$entity_type])) {
        $CONFIG->entity_url_handler[$entity_type] = array();
    }

    $CONFIG->entity_url_handler[$entity_type][$entity_subtype] = $function_name;

    return true;
}

/**
 * Registers an entity type and subtype as a public-facing entity that should
 * be shown in search and by {@link elgg_list_registered_entities()}.
 *
 * @warning Entities that aren't registered here will not show up in search.
 *
 * @tip Add a language string item:type:subtype to make sure the items are display properly.
 *
 * @param string $type    The type of entity (object, site, user, group)
 * @param string $subtype The subtype to register (may be blank)
 *
 * @return bool Depending on success
 * @see get_registered_entity_types()
 * @link http://docs.elgg.org/Search
 * @link http://docs.elgg.org/Tutorials/Search
 */
function elgg_register_entity_type($type, $subtype = null) {
    global $CONFIG;

    $type = strtolower($type);
    if (!in_array($type, $CONFIG->entity_types)) {
        return FALSE;
    }

    if (!isset($CONFIG->registered_entities)) {
        $CONFIG->registered_entities = array();
    }

    if (!isset($CONFIG->registered_entities[$type])) {
        $CONFIG->registered_entities[$type] = array();
    }

    if ($subtype) {
        $CONFIG->registered_entities[$type][] = $subtype;
    }

    return TRUE;
}

/**
 * Unregisters an entity type and subtype as a public-facing entity.
 *
 * @warning With a blank subtype, it unregisters that entity type including
 * all subtypes. This must be called after all subtypes have been registered.
 *
 * @param string $type    The type of entity (object, site, user, group)
 * @param string $subtype The subtype to register (may be blank)
 *
 * @return bool Depending on success
 * @see elgg_register_entity_type()
 */
function unregister_entity_type($type, $subtype) {
    global $CONFIG;

    $type = strtolower($type);
    if (!in_array($type, $CONFIG->entity_types)) {
        return FALSE;
    }

    if (!isset($CONFIG->registered_entities)) {
        return FALSE;
    }

    if (!isset($CONFIG->registered_entities[$type])) {
        return FALSE;
    }

    if ($subtype) {
        if (in_array($subtype, $CONFIG->registered_entities[$type])) {
            $key = array_search($subtype, $CONFIG->registered_entities[$type]);
            unset($CONFIG->registered_entities[$type][$key]);
        } else {
            return FALSE;
        }
    } else {
        unset($CONFIG->registered_entities[$type]);
    }

    return TRUE;
}

/**
 * Returns registered entity types and subtypes
 *
 * @param string $type The type of entity (object, site, user, group) or blank for all
 *
 * @return array|false Depending on whether entities have been registered
 * @see elgg_register_entity_type()
 */
function get_registered_entity_types($type = null) {
    global $CONFIG;

    if (!isset($CONFIG->registered_entities)) {
        return false;
    }
    if ($type) {
        $type = strtolower($type);
    }
    if (!empty($type) && empty($CONFIG->registered_entities[$type])) {
        return false;
    }

    if (empty($type)) {
        return $CONFIG->registered_entities;
    }

    return $CONFIG->registered_entities[$type];
}

/**
 * Returns if the entity type and subtype have been registered with {@see elgg_register_entity_type()}.
 *
 * @param string $type    The type of entity (object, site, user, group)
 * @param string $subtype The subtype (may be blank)
 *
 * @return bool Depending on whether or not the type has been registered
 */
function is_registered_entity_type($type, $subtype = null) {
    global $CONFIG;

    if (!isset($CONFIG->registered_entities)) {
        return false;
    }

    $type = strtolower($type);

    // @todo registering a subtype implicitly registers the type.
    // see #2684
    if (!isset($CONFIG->registered_entities[$type])) {
        return false;
    }

    if ($subtype && !in_array($subtype, $CONFIG->registered_entities[$type])) {
        return false;
    }
    return true;
}

/**
 * Page handler for generic entities view system
 *
 * @param array $page Page elements from pain page handler
 *
 * @return bool
 * @elgg_page_handler view
 * @access private
 */
function entities_page_handler($page) {
    if (isset($page[0])) {
        global $CONFIG;
        set_input('guid', $page[0]);
        include($CONFIG->path . "pages/entities/index.php");
        return true;
    }
    return false;
}

/**
 * Returns a viewable list of entities based on the registered types.
 *
 * @see elgg_view_entity_list
 *
 * @param array $options Any elgg_get_entity() options plus:
 *
 *  full_view => BOOL Display full view entities
 *
 *  list_type_toggle => BOOL Display gallery / list switch
 *
 *  allowed_types => TRUE|ARRAY True to show all types or an array of valid types.
 *
 *  pagination => BOOL Display pagination links
 *
 * @return string A viewable list of entities
 * @since 1.7.0
 */
function elgg_list_registered_entities(array $options = array()) {
    global $autofeed;
    $autofeed = true;

    $defaults = array(
        'full_view' => TRUE,
        'allowed_types' => TRUE,
        'list_type_toggle' => FALSE,
        'pagination' => TRUE,
        'offset' => 0,
        'types' => array(),
        'type_subtype_pairs' => array()
    );

    $options = array_merge($defaults, $options);

    //backwards compatibility
    if (isset($options['view_type_toggle'])) {
        $options['list_type_toggle'] = $options['view_type_toggle'];
    }

    $types = get_registered_entity_types();

    foreach ($types as $type => $subtype_array) {
        if (in_array($type, $options['allowed_types']) || $options['allowed_types'] === TRUE) {
            // you must explicitly register types to show up in here and in search for objects
            if ($type == 'object') {
                if (is_array($subtype_array) && count($subtype_array)) {
                    $options['type_subtype_pairs'][$type] = $subtype_array;
                }
            } else {
                if (is_array($subtype_array) && count($subtype_array)) {
                    $options['type_subtype_pairs'][$type] = $subtype_array;
                } else {
                    $options['type_subtype_pairs'][$type] = ELGG_ENTITIES_ANY_VALUE;
                }
            }
        }
    }

    if (!empty($options['type_subtype_pairs'])) {
        $count = elgg_get_entities(array_merge(array('count' => TRUE), $options));
        $entities = elgg_get_entities($options);
    } else {
        $count = 0;
        $entities = array();
    }

    $options['count'] = $count;
    return elgg_view_entity_list($entities, $options);
}

/**
 * Checks if $entity is an ElggEntity and optionally for type and subtype.
 *
 * @tip Use this function in actions and views to check that you are dealing
 * with the correct type of entity.
 *
 * @param mixed  $entity  Entity
 * @param string $type    Entity type
 * @param string $subtype Entity subtype
 * @param string $class   Class name
 *
 * @return bool
 * @since 1.8.0
 */
function elgg_instanceof($entity, $type = NULL, $subtype = NULL, $class = NULL) {
    $return = ($entity instanceof ElggEntity);

    if ($type) {
        /* @var ElggEntity $entity */
        $return = $return && ($entity->getType() == $type);
    }

    if ($subtype) {
        $return = $return && ($entity->getSubtype() == $subtype);
    }

    if ($class) {
        $return = $return && ($entity instanceof $class);
    }

    return $return;
}

/**
 * Update the last_action column in the entities table for $guid.
 *
 * @warning This is different to time_updated.  Time_updated is automatically set,
 * while last_action is only set when explicitly called.
 *
 * @param int $guid   Entity annotation|relationship action carried out on
 * @param int $posted Timestamp of last action
 *
 * @return bool
 * @access private
 */
function update_entity_last_action($guid, $posted = NULL) {
    global $CONFIG;
    $guid = (int)$guid;
    $posted = (int)$posted;

    if (!$posted) {
        $posted = time();
    }

    if ($guid) {
        //now add to the river updated table
        $query = "UPDATE {$CONFIG->dbprefix}entities SET last_action = {$posted} WHERE guid = {$guid}";
        $result = update_data($query);
        if ($result) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
}

/**
 * Garbage collect stub and fragments from any broken delete/create calls
 *
 * @return void
 * @elgg_plugin_hook_handler gc system
 * @access private
 */
function entities_gc() {
    global $CONFIG;

    $tables = array ('sites_entity', 'objects_entity', 'groups_entity', 'users_entity');

    foreach ($tables as $table) {
        delete_data("DELETE from {$CONFIG->dbprefix}{$table}
            where guid NOT IN (SELECT guid from {$CONFIG->dbprefix}entities)");
    }
}

/**
 * Runs unit tests for the entity objects.
 *
 * @param string  $hook   unit_test
 * @param string $type   system
 * @param mixed  $value  Array of tests
 * @param mixed  $params Params
 *
 * @return array
 * @access private
 */
function entities_test($hook, $type, $value, $params) {
    global $CONFIG;
    $value[] = $CONFIG->path . 'engine/tests/objects/entities.php';
    return $value;
}

/**
 * Entities init function; establishes the default entity page handler
 *
 * @return void
 * @elgg_event_handler init system
 * @access private
 */
function entities_init() {
    elgg_register_plugin_hook_handler('gc', 'system', 'entities_gc');
}

/** Register the import hook */
elgg_register_plugin_hook_handler("import", "all", "import_entity_plugin_hook", 0);

/** Register the hook, ensuring entities are serialised first */
elgg_register_plugin_hook_handler("export", "all", "export_entity_plugin_hook", 0);

/** Hook to get certain named bits of volatile data about an entity */
elgg_register_plugin_hook_handler('volatile', 'metadata', 'volatile_data_export_plugin_hook');

/** Register init system event **/
elgg_register_event_handler('init', 'system', 'entities_init');
