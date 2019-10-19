<?php
namespace Minds\Core\Notification\Settings;

use Minds\Entities;
use Minds\Core\Session;
use Minds\Core\Data;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\Notification\Extensions\Push;
use Minds\Entities\Factory as EntitiesFactory;

class PushSettings
{
    /** @var Data\Call */
    private $db;

    /** @var array */
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
    ];

    /** @var string */
    protected $userGuid;

    /** @var array */
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
     * @return self
     */
    public function setToggle($toggle, $value): self
    {
        $this->types[$toggle] = $value;
        $this->toBeSaved[$toggle] = $value;
        return $this;
    }

    /**
     * Batch sets toggles
     * @return self
     */
    public function setToggles($toggles = []): self
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
