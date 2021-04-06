<?php
namespace Minds\Core\Matrix;

use Minds\Traits\MagicAttributes;

/**
 * @method self setId(string $id)
 * @method string getId()
 * @method self setInvite(bool $invite)
 * @method bool isInvite()
 * @method self setDirectMessage(bool $directMessage)
 * @method bool isDirectMessage()
 * @method self setName(string $name)
 * @method string getName()
 * @method self setAvatarUrl(string $avatarUrl)
 * @method string getAvatarUrl()
 * @method self setUnreadCount(int $unreadCount)
 * @method int getUnreadCount()
 * @method self setLastEvent(int $lastEvent)
 * @method int getLastEvent()
 * @method self setMembers(array $members)
 * @method array getMembers()
 */
class MatrixRoom
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var bool */
    protected $invite = false;

    /** @var bool */
    protected $directMessage = false;

    /** @var string */
    protected $name;

    /** @var string */
    protected $avatarUrl;

    /** @var int */
    protected $unreadCount = 0;

    /** @var int */
    protected $lastEvent;

    /** @var array */
    protected $members;

    /**
     * Public export
     * @return array
     */
    public function export()
    {
        return [
            'id' => $this->id,
            'is_invite' => $this->isInvite(),
            'is_direct_message' => $this->isDirectMessage(),
            'name' => $this->getName(),
            'avatar_url' => $this->getAvatarUrl(),
            'unread_count' => $this->getUnreadCount(),
            'last_event' => $this->getLastEvent(),
            'members' => $this->getMembers(),
        ];
    }
}
