<?php
namespace Minds\Entities;

use Minds\Core;
use Minds\Helpers;

/**
 * User Entity
 * @todo Do not inherit from ElggUser
 */
class User extends \ElggUser
{
    protected function initializeAttributes() {
        $this->attributes['mature'] = 0;
        $this->attributes['social_profiles'] = [];

        parent::initializeAttributes();
    }

    /**
     * Sets the `mature` flag
     * @param  bool|int $value
     * @return $this
     */
    public function setMature($value)
    {
        $this->mature = $value ? 1 : 0;
        return $this;
    }

    /**
     * Gets the `mature` flag
     * @return bool|int
     */
    public function getMature()
    {
      return $this->mature;
    }

    /**
     * Sets and encrypts a users email address
     * @param  string $email
     * @return $this
     */
    public function setEmail($email)
    {
        global $CONFIG; //@todo use object config instead
        if (base64_decode($email, true)) {
            return $this;
        }
        $this->email = base64_encode(Helpers\OpenSSL::encrypt($email, file_get_contents($CONFIG->encryptionKeys['email']['public'])));
        return $this;
    }

    /**
     * Returns and decrypts an email address
     * @return $this
     */
    public function getEmail()
    {
        global $CONFIG; //@todo use object config instead
        if ($this->email && !base64_decode($this->email, true)) {
            return $this->email;
        }
        return Helpers\OpenSSL::decrypt(base64_decode($this->email), file_get_contents($CONFIG->encryptionKeys['email']['private']));
    }

    /**
     * Sets (overrides) social profiles information
     * @return $this
     */
    public function setSocialProfiles(array $social_profiles)
    {
        $this->social_profiles = $social_profiles;
        return $this;
    }

    /**
     * Sets (or clears) a single social profile
     * @return $this
     */
    public function setSocialProfile($key, $value = null)
    {
        if ($value === null || $value === '') {
            if (isset($this->social_profiles[$key])) {
                unset($this->social_profiles[$key]);
            }
        } else {
            $this->social_profiles[$key] = $value;
        }

        return $this;
    }

    /**
     * Returns all set social profiles
     * @return array
     */
    public function getSocialProfiles()
    {
        return $this->social_profiles ?: [];
    }

    /**
     * Subscribes user to another user
     * @param  mixed  $guid
     * @param  array  $data - metadata
     * @return mixed
     */
    public function subscribe($guid, $data = array())
    {
        return \Minds\Helpers\Subscriptions::subscribe($this->guid, $guid, $data);
    }

    /**
     * Unsubscribes from another user
     * @param  mixed $guid
     * @return mixed
     */
    public function unSubscribe($guid)
    {
        return \Minds\Helpers\Subscriptions::unSubscribe($this->guid, $guid, $data);
    }

    /**
     * Checks if subscribed to another user.
     * @param  mixed $guid   - the user to check subscription to
     * @return boolean
     */
    public function isSubscriber($guid)
    {
        $cacher = Core\Data\cache\factory::build();

        if ($cacher->get("$this->guid:isSubscriber:$guid")) {
            return true;
        }
        if ($cacher->get("$this->guid:isSubscriber:$guid") === 0) {
            return false;
        }

        $return = 0;
        $db = new Core\Data\Call('friendsof');
        $row = $db->getRow($this->guid, array('limit'=> 1, 'offset'=>$guid));
        if ($row && key($row) == $guid) {
            $return = true;
        }

        $cacher->set("$this->guid:isSubscriber:$guid", $return);

        return $return;
    }

    /**
     * Checks if subscribed to another user in a
     * reversed way than isSubscribed()
     * @param  mixed $guid   - the user to check subscription to
     * @return boolean
     */
    public function isSubscribed($guid)
    {
        $cacher = Core\Data\cache\factory::build();

        if ($cacher->get("$this->guid:isSubscribed:$guid")) {
            return true;
        }
        if ($cacher->get("$this->guid:isSubscribed:$guid") === 0) {
            return false;
        }

        $return = 0;
        $db = new Core\Data\Call('friends');
        $row = $db->getRow($this->guid, array('limit'=> 1, 'offset'=>$guid));
        if ($row && key($row) == $guid) {
            $return = true;
        }

        $cacher->set("$this->guid:isSubscribed:$guid", $return);

        return $return ;
    }

    public function getSubscribersCount()
    {
        if ($this->host) {
            return 0;
        }

        $cacher = Core\Data\cache\factory::build();
        if ($cache = $cacher->get("$this->guid:friendsofcount")) {
            return $cache;
        }

        $db = new Core\Data\Call('friendsof');
        $return = (int) $db->countRow($this->guid);
        $cacher->set("$this->guid:friendsofcount", $return, 360);
        return (int) $return;
    }

    /**
     * Gets the number of subscriptions
     * @return int
     */
    public function getSubscriptonsCount()
    {
        if ($this->host) {
            return 0;
        }

        $cacher = Core\Data\cache\factory::build();
        if ($cache = $cacher->get("$this->guid:friendscount")) {
            return $cache;
        }

        $db = new Core\Data\Call('friends');
        $return = (int) $db->countRow($this->guid);
        $cacher->set("$this->guid:friendscount", $return, 360);
        return (int) $return;
    }

    /**
     * Set the secret key for clusters to use
     * @todo - should we use oauth2 instead. should this be stored in its own row rather than in the user object?
     * @param string $host
     */
    public function setSecretKey($host)
    {
        $key = "secret:" . serialize($host);
        $this->$key = core\clusters::generateSecret();
        $this->save();
    }

    /**
     * Exports to an array
     * @return array
     */
    public function export()
    {
        $export = parent::export();
        $export['guid'] = (string) $this->guid;
        if (Core\Session::isLoggedIn()) {
            $export['subscribed'] = elgg_get_logged_in_user_entity()->isSubscribed($this->guid);
            $export['subscriber'] = elgg_get_logged_in_user_entity()->isSubscriber($this->guid);
        }
        if ($this->username != "minds") {
            $export['subscribers_count'] = $this->getSubscribersCount();
        }
        $export['subscriptions_count'] = $this->getSubscriptionsCount();
        $export['impressions'] = $this->getImpressions();
        if ($this->fb && is_string($this->fb)) {
            $export['fb'] = json_decode($this->fb, true);
        }

        if (isset($export['mature'])) {
            $export['mature'] = (int) $export['mature'];
        }
        return $export;
    }

    /**
     * Get the number of impressions for the user
     * @return int
     */
    public function getImpressions()
    {
        $app = Core\Analytics\App::_()
                ->setMetric('impression')
                ->setKey($this->guid);
        return $app->total();
    }

    /**
     * Gets the user's icon URL
     * @param  string $size
     * @return string
     */
    public function getIconURL($size = 'medium')
    {
        $join_date = $this->getTimeCreated();
        return elgg_get_site_url() . "icon/$this->guid/$size/$join_date/$this->icontime/" . Core\Config::_()->lastcache;
    }

    /**
     * Returns an array of which Entity attributes are exportable
     * @return array
     */
    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), array(
            'website',
            'briefdescription',
            'dob',
            'gender',
            'city',
            'merchant',
            'boostProPlus',
            'fb',
            'mature',
            'monetized',
            'signup_method',
            'social_profiles'
        ));
    }
}
