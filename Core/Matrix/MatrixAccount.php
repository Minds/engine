<?php
namespace Minds\Core\Matrix;

use Minds\Traits\MagicAttributes;

/**
 * @method self setId(string $id)
 * @method string getId()
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDisplayName(string $displayName)
 * @method string getDisplayName()
 * @method self setAvatarUrl(string $avatarUrl)
 * @method string getAvatarUrl()
 * @method self setDeactivated(bool $deactivated)
 * @method bool getDeactivated()
 * @method bool isDeactivated()
 */
class MatrixAccount
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $userGuid;

    /** @var string */
    protected $displayName;

    /** @var string */
    protected $avatarUrl;

    /** @var bool */
    protected $deactivated = false;

    /**
     * Public export
     * @return array
     */
    public function export()
    {
        return [
            'id' => $this->id,
            'user_guid' => (string) $this->userGuid,
            'display_name' => $this->displayName,
            'avatar_url' => $this->avatarUrl,
        ];
    }
}
