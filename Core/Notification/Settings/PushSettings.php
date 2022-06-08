<?php
namespace Minds\Core\Notification\Settings;

use Minds\Core\Data;

class PushSettings
{
    protected $db;

    protected $types = [
      'daily' => true,
      'comment' => true,
      'message' => true,
      'like' => true,
      'tag' => true,
      'friends' => true,
      'remind' => true,
      'boost_gift' => true,
      'boost_request' => true,
      'boost_accepted' => true,
      'boost_rejected' => true,
      'boost_revoked' => true,
      'boost_completed' => true,
      'group_invite' => true,
      'messenger_invite' => true,
      'referral_ping' => true,
      'referral_pending' => true,
      'referral_complete' => true,
      'rewards_summary' => true,
      'custom_message' => true,
      'community_updates' => true,
    ];
    protected $userGuid;
    protected $toBeSaved = [];

    public function __construct($db = null)
    {
        $this->db = $db ?: new Data\Call('entities_by_time');
    }

    /**
     * Set user guid
     * @return $this
     */
    public function setUserGuid($guid)
    {
        $this->userGuid = $guid;
        return $this;
    }

    /**
     * Return toggles for notifications
     * @return array
     */
    public function getToggles()
    {
        $types = $this->db->getRow('settings:push:toggles:' . $this->userGuid) ?: [];
        foreach ($types as $toggle => $value) {
            $this->types[$toggle] = (bool) $value;
        }
        return $this->types;
    }

    /**
     * Sets an individual toggle
     * @return $this
     */
    public function setToggle($toggle, $value)
    {
        $this->types[$toggle] = $value;
        $this->toBeSaved[$toggle] = $value;
        return $this;
    }

    /**
     * Batch sets toggles
     * @return this
     */
    public function setToggles($toggles = [])
    {
        $this->types = array_merge($this->types, $toggles);
        $this->toBeSaved = $toggles;
        return $this;
    }

    /**
     * Saves notifications
     * @return array
     */
    public function save()
    {
        $this->db->insert('settings:push:toggles:' . $this->userGuid, $this->toBeSaved);
        $this->toBeSaved = [];
        return $this;
    }
}
