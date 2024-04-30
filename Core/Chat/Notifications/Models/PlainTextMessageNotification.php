<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications\Models;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Entities\User;

class PlainTextMessageNotification extends AbstractChatNotification
{
    public function __construct(
        ?int    $chatRoomGuid = null,
        ?string $title = null,
        ?string $body = null,
        ?string $icon = null,
        protected ?Config $config = null
    ) {
        parent::__construct(
            chatRoomGuid: $chatRoomGuid,
            title: $title,
            body: $body,
            icon: $icon
        );
        
        $this->config ??= Di::_()->get(Config::class);
    }

    /**
     * @inheritDoc
     */
    public function getUserGuid(): ?string
    {
        return (string) $this->notificationRecipientGuid;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return  '💬 ' . $this->title;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): ?string
    {
        if (strlen($this->body) > 100) {
            return substr($this->body, 0, 100) . '...';
        }
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): ?string
    {
        return $this->getEnvBasedUri("chat/rooms/$this->chatRoomGuid");
    }

    /**
     * @inheritDoc
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @inheritDoc
     */
    public function getMedia(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getDeviceSubscription(): DeviceSubscription
    {
        return $this->deviceSubscription;
    }

    /**
     * @inheritDoc
     */
    public function getGroup(): string
    {
        return "chat_$this->chatRoomGuid";
    }

    /**
     * @inheritDoc
     */
    public function getMergeKey(): string
    {
        return "chat_$this->chatRoomGuid";
    }

    /**
     * @inheritDoc
     */
    public function getUnreadCount(): int
    {
        return 1; // TODO: update with real value
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        return [];
    }

    public function fromEntity(
        ChatMessage $chatMessage,
        User $sender
    ): self {
        return new self(
            chatRoomGuid: $chatMessage->roomGuid,
            title: $sender->getName(),
            body: $chatMessage->plainText,
            icon: $sender->getIconURL('large')
        );
    }
}
