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

    if ($new_entity) {
        return $new_entity;
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
            default:
                $msg ='InstallationException:TypeNotSupported ' . $row->type;
                throw new InstallationException($msg);
        }
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
        cache_entity($new_entity);
    }
    return $new_entity;
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
        'acl'                   => true,
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
                        if($options['container_guids'] && $container_guid = $options['container_guids'][0]){
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

                    if (!$entity) {
                        continue;
                    }

                    if ($options['acl'] === true && !Minds\Core\Security\ACL::_()->read($entity)) {
                        continue;
                    }

                    $entities[] = $entity;
                }
            }
        } catch(Exception $e){
            //var_dump($e);
            //@todo report error to admin
        }
        return $entities;

}
