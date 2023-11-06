<?php

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\UndeliveredEventException;
use Minds\Entities\CommentableEntityInterface;
use Minds\Entities\EntityInterface;
use Minds\Exceptions\ObsoleteCodeException;
use Minds\Helpers\StringLengthValidators\MessageLengthValidator;
use Minds\Helpers\StringLengthValidators\TitleLengthValidator;

/**
 * The parent class for all Elgg Entities.
 *
 *
 * @package    Elgg.Core
 * @subpackage DataModel.Entities
 *
 * @property string $type           object, user, group, or site (read-only after save)
 * @property string $subtype        Further clarifies the nature of the entity (read-only after save)
 * @property int    $guid           The unique identifier for this entity (read only)
 * @property int    $owner_guid     The GUID of the creator of this entity
 * @property int    $container_guid The GUID of the entity containing this entity
 * @property int    $site_guid      The GUID of the website this entity is associated with
 * @property int    $access_id      Specifies the visibility level of this entity
 * @property int    $time_created   A UNIX timestamp of when the entity was created (read-only, set on first save)
 * @property int    $time_updated   A UNIX timestamp of when the entity was last updated (automatically updated on save)
 * @property int    $moderator_guid The GUID of the moderator
 * @property int    $moderated_at   A UNIX timestamp of when the entity was moderated
 * @property string $enabled
 */
abstract class ElggEntity extends ElggData implements
    EntityInterface
{
    protected $cache = true;

    /**
     * If set, overrides the value of getURL()
     */
    protected $url_override;

    /**
     * Icon override, overrides the value of getIcon().
     */
    protected $icon_override;


    /**
     * Volatile data structure for this object, allows for storage of data
     * in-memory that isn't sync'd back to the metadata table.
     */
    protected $volatile = [];

    protected $_context = 'default';

    /**
     * Default a fields isJSON checks to false.
     */
    protected $nonJsonFields = [
        'message',
        'description',
        'title'
    ];

    /**
     * Initialize the attributes array.
     *
     * This is vital to distinguish between metadata and base parameters.
     *
     * @return void
     */
    protected function initializeAttributes()
    {
        parent::initializeAttributes();

        $this->attributes['guid'] = null;
        $this->attributes['type'] = null;
        $this->attributes['subtype'] = null;

        $this->attributes['owner_guid'] = elgg_get_logged_in_user_guid();
        $this->attributes['container_guid'] = elgg_get_logged_in_user_guid();

        $this->attributes['site_guid'] = null;
        $this->attributes['access_id'] = Di::_()->get('Config')->get('default_access');
        $this->attributes['time_created'] = time();
        $this->attributes['time_updated'] = time();
        $this->attributes['last_action'] = null;
        $this->attributes['tags'] = null;
        $this->attributes['nsfw'] = [];
        $this->attributes['nsfw_lock'] = [];
        $this->attributes['moderator_guid'] = null;
        $this->attributes['time_moderated'] = null;
    }

    /**
     * Entity constructor
     */
    public function __construct($data = null)
    {
        $this->initializeAttributes();

        if ($data) {
            if (is_numeric($data)) {
                throw new ObsoleteCodeException();
            } elseif (is_object($data)) {
                $this->loadFromObject($data);
            } elseif (is_array($data)) {
                $this->loadFromArray($data);
            }
        }
    }

    protected function loadFromObject($object)
    {
        $this->loadFromArray($object);
    }

    protected function loadFromArray($array)
    {
        foreach ($array as $k=>$v) {
            if ($this->isJson($v, $k)) {
                $v = json_decode($v, true);
            }

            $this->$k = $v;
        }

        if ($this->cache) {
            cache_entity($this);
        }
    }


    /**
     * Returns true if string contains valid JSON that is not on the exceptions list.
     * @param $string - string to check.
     * @param $key - key to check.
     * @return bool - true if valid json not on exceptions list.
     */
    public function isJson($string, $key = ''): bool
    {
        // if its not a string, false
        if (!is_string($string)) {
            return false;
        }

        try {
            // 'true' and 'false' are valid for some fields.
            if (in_array($key, $this->nonJsonFields, true)) {
                return false;
            }
        } catch (\Exception $e) {
            // do nothing.
        }

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    /**
     * Return the value of a property.
     *
     * @param string $name Name
     *
     * @return mixed Returns the value of a given value, or null.
     */
    public function get($name)
    {
        // See if its in our base attributes
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return false;
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }
    /**
     * Sets the value of a property.
     *
     * @param string $name  Name
     * @param mixed  $value Value
     *
     * @return bool
     */
    public function set($name, $value)
    {
        switch ($name) {
            case 'access_id': // a hack to fix listings.
                if ($value == ACCESS_DEFAULT) {
                    $value = Di::_()->get('Config')->get('default_access');
                }
                // no break
            default:
                $this->attributes[$name] = $value;
                break;
        }

        return $this;
    }


    /**
     * Unset a property from metadata or attribute.
     *
     * @warning If you use this to unset an attribute, you must save the object!
     *
     * @param string $name The name of the attribute or metadata.
     *
     * @return void
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            $this->attributes[$name] = "";
        }
    }

    /**
     * Can a user edit this entity.
     *
     * @param int $user_guid The user GUID, optionally (default: logged in user)
     *
     * @return bool
     */
    public function canEdit($user_guid = 0)
    {
        return Minds\Core\Security\ACL::_()->write($this);
    }

    /**
     * Returns the access_id.
     *
     * @return string The access ID
     */
    public function getAccessId(): string
    {
        return (string) $this->get('access_id');
    }

    /**
     * Setter for access_id.
     * @return self instance of this.
     */
    public function setAccessId(string $accessId): self
    {
        $this->access_id = $accessId;
        return $this;
    }

    /**
     * Returns the guid.
     *
     * @return int|null GUID
     */
    public function getGuid(): ?string
    {
        return (string) $this->get('guid');
    }

    /**
     * Returns the entity type
     *
     * @return string|null Entity type
     */
    public function getType(): ?string
    {
        return $this->get('type');
    }

    /**
     * Returns the entity subtype string
     *
     * @note This returns a string.  If you want the id, use ElggEntity::subtype.
     *
     * @return string The entity subtype
     */
    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    /**
     * Get the guid of the entity's owner.
     *
     * @return int The owner GUID
     */
    public function getOwnerGuid(): ?string
    {
        return (string) $this->owner_guid;
    }

    /**
     * Return the guid of the entity's owner.
     *
     * @return int The owner GUID
     * @deprecated 1.8 Use getOwnerGUID()
     */
    public function getOwner()
    {
        elgg_deprecated_notice("ElggEntity::getOwner deprecated for ElggEntity::getOwnerGUID", 1.8);
        return $this->getOwnerGUID();
    }

    /**
     * Set the container for this object.
     *
     * @param int $container_guid The ID of the container.
     *
     * @return bool
     */
    public function setContainerGUID($container_guid)
    {
        $container_guid = (int)$container_guid;

        return $this->set('container_guid', $container_guid);
    }

    /**
     * Gets the container GUID for this entity.
     *
     * @return int
     */
    public function getContainerGuid()
    {
        return $this->get('container_guid');
    }

    /**
     * Get the container entity for this object.
     * Assume contrainer entity is a user, unless another class overrides this...
     *
     * @return ElggEntity
     * @since 1.8.0
     */
    public function getContainerEntity()
    {
        return Di::_()->get(EntitiesBuilder::class)->single($this->getContainerGUID());
    }

    /**
     * Returns the UNIX epoch time that this entity was last updated
     *
     * @return int UNIX epoch time
     */
    public function getTimeUpdated()
    {
        return $this->get('time_updated');
    }

    /**
     * Returns the URL for this entity
     *
     * @return string The URL
     * @see register_entity_url_handler()
     * @see ElggEntity::setURL()
     */
    public function getURL()
    {
        if (!empty($this->url_override)) {
            return $this->url_override;
        }
        return '';
    }

    public function getPermaURL()
    {
        return $this->perma_url ?: $this->getURL();
    }

    /**
     * Overrides the URL returned by getURL()
     *
     * @warning This override exists only for the life of the object.
     *
     * @param string $url The new item URL
     *
     * @return string The URL
     */
    public function setURL($url)
    {
        $this->url_override = $url;
        return $url;
    }

    /**
     * Get the URL for this entity's icon
     *
     * Plugins can register for the 'entity:icon:url', <type> plugin hook
     * to customize the icon for an entity.
     *
     * @param string $size Size of the icon: tiny, small, medium, large
     *
     * @return string The URL
     * @since 1.8.0
     */
    public function getIconURL($size = 'medium')
    {
        $size = strtolower($size);

        if (isset($this->icon_override[$size])) {
            elgg_deprecated_notice("icon_override on an individual entity is deprecated", 1.8);
            return $this->icon_override[$size];
        }

        $url = "_graphics/icons/default/$size.png";
    
        return $url;
    }

    /**
     * Returns a URL for the entity's icon.
     *
     * @param string $size Either 'large', 'medium', 'small' or 'tiny'
     *
     * @return string The url or false if no url could be worked out.
     * @deprecated Use getIconURL()
     */
    public function getIcon($size = 'medium')
    {
        elgg_deprecated_notice("getIcon() deprecated by getIconURL()", 1.8);
        return $this->getIconURL($size);
    }

    /**
     * Set an icon override for an icon and size.
     *
     * @warning This override exists only for the life of the object.
     *
     * @param string $url  The url of the icon.
     * @param string $size The size its for.
     *
     * @return bool
     * @deprecated 1.8 See getIconURL() for the plugin hook to use
     */
    public function setIcon($url, $size = 'medium')
    {
        elgg_deprecated_notice("icon_override on an individual entity is deprecated", 1.8);

        if (!$this->icon_override) {
            $this->icon_override = [];
        }
        $this->icon_override[$size] = $url;

        return true;
    }

    /**
     * Tests to see whether the object has been fully loaded.
     *
     * @return bool
     */
    public function isFullyLoaded()
    {
        return true;
    }

    /**
     * Loads attributes from the entities table into the object.
     *
     * @param mixed $guid GUID of entity or stdClass object from entities table
     *
     * @return bool
     */
    protected function load($guid)
    {
        if ($guid instanceof stdClass) {
            $row = $guid;
        } else {
            return false;
        }

        if ($row) {
            // Create the array if necessary - all subclasses should test before creating
            if (!is_array($this->attributes)) {
                $this->attributes = [];
            }

            // Now put these into the attributes array as core values
            $objarray = (array) $row;
            foreach ($objarray as $key => $value) {
                $this->attributes[$key] = $value;
            }

            // Increment the portion counter
            if (!$this->isFullyLoaded()) {
                $this->attributes['tables_loaded']++;
            }

            // guid needs to be an int  http://trac.elgg.org/ticket/4111
            $this->attributes['guid'] = (int)$this->attributes['guid'];

            // Cache object handle
            if ($this->attributes['guid']) {
                cache_entity($this);
            }

            return true;
        }

        return false;
    }

    /**
     * Is this entity enabled?
     *
     * @return boolean
     */
    public function isEnabled()
    {
        if ($this->enabled == 'yes') {
            return true;
        }

        return false;
    }

    /**
     * Returns an array of indexes into which this entity is stored
     *
     * @param bool $ia - ignore access
     * @return array
     */
    protected function getIndexKeys($ia = false)
    {
        //remove from the various lines
        if ($this->access_id == ACCESS_PUBLIC || $ia) {
            $indexes = [
                $this->type,
                "$this->type:$this->subtype"
            ];

            if ($this->super_subtype) {
                array_push($indexes, "$this->type:$this->super_subtype");
            }
        } else {
            $indexes = [];
        }

        if (!$this->hidden) {
            array_push($indexes, "$this->type:$this->super_subtype:user:$this->owner_guid");
            array_push($indexes, "$this->type:$this->subtype:user:$this->owner_guid");
        } else {
            array_push($indexes, "$this->type:$this->super_subtype:user:$this->owner_guid:hidden");
            array_push($indexes, "$this->type:$this->subtype:user:$this->owner_guid:hidden");
        }

        array_push($indexes, "$this->type:container:$this->container_guid");
        array_push($indexes, "$this->type:$this->subtype:container:$this->container_guid");


        return $indexes;
    }


    /*
     * EXPORTABLE INTERFACE
     */

    /**
     * Returns an array of fields which can be exported.
     *
     * @return array
     */
    public function getExportableValues()
    {
        return [
            'guid',
            'type',
            'subtype',
            'time_created',
            'time_updated',
            'container_guid',
            'owner_guid',
            'site_guid',
            'access_id',
            'tags',
            'nsfw',
            'nsfw_lock',
        ];
    }

    public function export()
    {
        $export = [];
        foreach ($this->getExportableValues() as $v) {
            if (!is_null($this->$v)) {
                $export[$v] = $this->$v;
            }
        }
        $export = array_merge($export, \Minds\Core\Events\Dispatcher::trigger('export:extender', 'all', ['entity'=>$this], []) ?: []);
        $export = \Minds\Helpers\Export::sanitize($export);
        $export['nsfw'] = $this->getNsfw();
        $export['nsfw_lock'] = $this->getNsfwLock();
        $export['urn'] = $this->getUrn();

        if (isset($export['title'])) {
            $export['title'] = (new TitleLengthValidator())->validateMaxAndTrim($export['title']);
        }
        if (isset($export['message'])) {
            $export['message'] = (new MessageLengthValidator())->validateMaxAndTrim($export['message']);
        }

        return $export;
    }

    public function context($context = 'default')
    {
        $this->_context = $context;
    }

    public function getContext()
    {
        return $this->_context;
    }

    public function isContext($context)
    {
        return $this->getContext() == $context;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        // There is a bug on mobile that is sending the hashtags with the hash, this shouldnt happen
        $this->tags = array_map(function ($tag) {
            return (string) str_replace('#', '', $tag);
        }, $this->tags ?: []);
        return $this->tags ?: [];
    }

    /**
     * @param array $value
     * @return $this
     */
    public function setTags(array $value)
    {
        $this->tags = $value;
        return $this;
    }

    public function getRating()
    {
        $this->rating = (int) $this->rating;
        return $this->rating === 0 ? 1 : $this->rating;
    }

    public function setRating($value)
    {
        if ($value < 1 && $value > 3) {
            $this->rating = 1;
        }
        $this->rating = $value;
        return $this;
    }

    /**
     * Get NSFW options
     * @return array
     */
    public function getNsfw()
    {
        $array = [];
        if (!$this->nsfw) {
            return $array;
        }
        foreach ($this->nsfw as $reason) {
            $array[] = (int) $reason;
        }
        return $array;
    }

    /**
     * Set NSFW tags
     * @param array $array
     * @return $this
     */
    public function setNsfw($array)
    {
        $array = array_unique($array);
        foreach ($array as $reason) {
            if ($reason < 1 || $reason > 6) {
                throw new \Exception('Incorrect NSFW value provided');
            }
        }
        
        $this->nsfw = $array;
        return $this;
    }

    /**
    * Get NSFW Lock options.
    *
    * @return array
    */
    public function getNsfwLock()
    {
        $array = [];
        if (!$this->nsfwLock) {
            return $array;
        }
        foreach ($this->nsfwLock as $reason) {
            $array[] = (int) $reason;
        }

        return $array;
    }
    
    /**
     * Set NSFW lock tags for administrators. Users cannot remove these themselves.
     *
     * @param array $array
     *
     * @return $this
     */
    public function setNsfwLock($array)
    {
        $array = array_unique($array);
        foreach ($array as $reason) {
            if ($reason < 1 || $reason > 6) {
                throw \Exception('Incorrect NSFW value provided');
            }
        }
        $this->nsfwLock = $array;

        return $this;
    }

    /**
     * Return a preferred urn
     * @return string
     */
    public function getUrn(): string
    {
        return "urn:entity:{$this->getGuid()}";
    }

    /** gets the guid of the moderator
     * @return int
     */
    public function getModeratorGuid()
    {
        return $this->moderator_guid;
    }


    /**
     * Marks the user as moderated by a user
     * @param int $moderatorGuid the moderator
     */
    public function setModeratorGuid(int $moderatorGuid)
    {
        $this->moderator_guid = $moderatorGuid;
    }

    /**
     * Marks the time as when an entity was moderated
     * @param int $timeModerated unix timestamp when the entity was moderated
     */
    public function setTimeModerated(int $timeModerated)
    {
        $this->time_moderated = $timeModerated;
    }
    
    /**
     * Gets the time moderated
     * @return int
     */
    public function getTimeModerated()
    {
        return $this->time_moderated;
    }

}
