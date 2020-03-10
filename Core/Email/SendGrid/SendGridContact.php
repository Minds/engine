<?php
namespace Minds\Core\Email\SendGrid;

use Minds\Traits\MagicAttributes;

/**
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
 */
class SendGridContact
{
    use MagicAttributes;

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
        $customFields['username'] = $this->username;
        $customField['user_guid'] = (string) $this->userGuid;
        return [
            'email' => $this->email,
            'first_name' => (string) $this->username,
            'unique_name' => strtolower($this->username),
            'custom_fields' => $customFields,
        ];
    }
}
