<?php

use Minds\Common\IpAddress;
use Minds\Exceptions\ObsoleteCodeException;

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

        if (!empty($guid)) {
            // Is $guid is a DB entity row
            if ($guid instanceof stdClass) {
                // Load the rest
                if (!$this->load($guid)) {
                    $msg = 'IOException:FailedToLoadGUID ' . get_class() . ' ' . $guid->guid;
                    throw new IOException($msg);
                }
            } elseif (is_numeric($guid) && strlen((string) $guid) >= 18) {
                throw new ObsoleteCodeException();
            } elseif (is_string($guid)) {
                throw new ObsoleteCodeException();
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
    protected function load($data)
    {
        foreach ($data as $k => $v) {
            if ($this->isJson($v)) {
                $v = json_decode($v, true);
            }
            $this->attributes[$k] = $v;
        }

        return true;
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
     * Note: This value is set to true for
     * - all Minds admins
     * - Tenant Owners
     * - Tenant Admins
     *
     * @return bool
     */
    public function isAdmin()
    {
        $config = Minds\Core\Di\Di::_()->get('Config');

        if ($config->get('development_mode') !== true && !$config->get('tenant_id') && php_sapi_name() !== 'cli') {
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
     * Get a user's owner GUID
     *
     * Returns it's own GUID if the user is not owned.
     *
     * @return int
     */
    public function getOwnerGuid(): ?string
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
