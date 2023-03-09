<?php
namespace Minds\Core\Email\SendGrid;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method SendGridContact setUser(User $user)
 * @method User getUser()
 * @method SendGridContact setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method SendGridContact setEmail(string $email)
 * @method string getEmail()
 * @method SendGridContact setUsername(string $username)
 * @method string getUsername()
 * @method SendGridContact setIsPro(bool $isPro)
 * @method bool getIsPro()
 * @method SendGridContact setIsPlus(bool $isPlus)
 * @method bool getIsPlus()
 * @method SendGridContact setIsMerchant(bool $isMerchant)
 * @method bool getIsMerchant()
 * @method SendGridContact setLastWire(int $lastWire)
 * @method int getLastWire()
 * @method self setSubscribedTo(array $subscribedTo)
 */
class SendGridContact
{
    use MagicAttributes;

    /** @var User */
    protected $user;

    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $email;

    /** @var string */
    protected $username;

    /** @var int */
    protected $proExpires;

    /** @var int */
    protected $plusExpires;

    /** @var bool */
    protected $isMerchant;

    /** @var int */
    protected $lastWire;

    /** @var string */
    protected $ethWallet;

    /** @var bool */
    protected $hasMembershipTier;

    /** @var bool */
    protected $hasTwitterSync;

    /** @var bool */
    protected $hasYouTubeSync;

    /** @var int */
    protected $lastActive30DayTs;

    /** @var array */
    protected $subscribedTo = [];

    /** @var array */
    protected $fields = [];

    /**
     * Helper function to set the fields array
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->fields[$key] = $value;
        return $this;
    }

    /**
     * Export the sendgrid contact
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        $customFields = [];
        if ($this->proExpires) {
            $customFields['pro_expires'] = date('c', $this->proExpires);
        }
        if ($this->plusExpires) {
            $customFields['plus_expires'] = date('c', $this->plusExpires);
        }
        if ($this->isMerchant) {
            $customFields['merchant'] = 1;
        }
        if ($this->lastWire) {
            $customFields['last_wire'] = date('c', $this->lastWire);
        }
        if ($this->lastActive30DayTs) {
            $customFields['last_active_30_day_ts'] = date('c', $this->lastActive30DayTs);
        }
        if ($this->hasMembershipTier) {
            $customFields['has_membership_tier'] = 1;
        }
        if ($this->ethWallet) {
            $customFields['eth_wallet'] = $this->ethWallet;
        }
        if ($this->hasTwitterSync) {
            $customFields['has_twitter_sync'] = 1;
        }
        if ($this->hasYouTubeSync) {
            $customFields['has_youtube_sync'] = 1;
        }

        if ($this->subscribedTo) {
            foreach ($this->subscribedTo as $subscribedTo) {
                $customFields['sub_' . $subscribedTo] = 1;
            }
        }

        $customFields['username'] = $this->username;
        $customField['user_guid'] = (string) $this->userGuid;

        return [
            'email' => $this->email,
            'first_name' => substr((string) $this->username, 0, 50),
            'unique_name' => strtolower($this->username),
            'custom_fields' => array_merge($customFields, $this->fields),
        ];
    }
}
