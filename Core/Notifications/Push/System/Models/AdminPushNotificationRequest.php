<?php

namespace Minds\Core\Notifications\Push\System\Models;

use Minds\Core\Notifications\Push\System\AdminPushNotificationRequestStatus;
use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;
use Minds\Exceptions\ServerErrorException;
use Minds\Traits\MagicAttributes;

/**
 * @method self setRequestUuid(string $requestUuid)
 * @method string getRequestUuid()
 * @method self setAuthorGuid(string $authorGuid)
 * @method string getAuthorGuid()
 * @method self setTitle(string $title)
 * @method string getTitle()
 * @method self setMessage(string $title)
 * @method string|null getMessage()
 * @method self setLink(string $link)
 * @method string|null getLink()
 * @method self setTarget(string $target)
 * @method string getTarget()
 * @method self setCounter(int $counter)
 * @method int getCounter()
 * @method self setStatus(int $status)
 * @method self setCreatedAt(string $createdAt)
 * @method string getCreatedAt()
 * @method self setStartedAt(string $startedAt)
 * @method string getStartedAt()
 * @method self setCompletedAt(string $completedAt)
 * @method string getCompletedAt()
 */
class AdminPushNotificationRequest implements ExportableInterface, EntityInterface
{
    use MagicAttributes;

    public const URN_METHOD = 'system-push-notification';

    private string $type = 'admin';
    private string $requestUuid;
    private ?string $authorGuid;
    private string $title;
    private ?string $message;
    private ?string $link;
    private string $target;
    private ?int $counter;
    private int $status;
    private string $createdAt;
    private ?string $startedAt;
    private ?string $completedAt;

    /**
     * @throws ServerErrorException
     */
    public static function fromArray(array $data): self
    {
        $notificationData = new self;

        if (!isset($data['request_uuid'])) {
            throw new ServerErrorException("Missing property 'request_uuid' in System Push Notification event");
        }
        $notificationData->setRequestUuid($data['request_uuid']);

        if (!isset($data['author_guid'])) {
            throw new ServerErrorException("Missing property 'author_guid' in System Push Notification event");
        }
        $notificationData->setAuthorGuid($data['author_guid']);

        if (!isset($data['title'])) {
            throw new ServerErrorException("Missing property 'title' in System Push Notification event");
        }
        $notificationData->setTitle($data['title']);

        if (!key_exists('message', $data)) {
            throw new ServerErrorException("Missing property 'message' in System Push Notification event");
        }
        $notificationData->setMessage($data['message']);

        if (!key_exists('url', $data)) {
            throw new ServerErrorException("Missing property 'url' in System Push Notification event");
        }
        $notificationData->setLink($data['url']);

        if (!isset($data['created_at'])) {
            throw new ServerErrorException("Missing property 'created_at' in System Push Notification event");
        }
        $notificationData->setCreatedAt($data['created_at']);

        if (!isset($data['target'])) {
            throw new ServerErrorException("Missing property 'target' in System Push Notification event");
        }
        $notificationData->setTarget($data['target']);

        if (!isset($data['counter'])) {
            throw new ServerErrorException("Missing property 'counter' in System Push Notification event");
        }
        $notificationData->setCounter($data['counter']);

        return $notificationData;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return AdminPushNotificationRequestStatus::statusLabelFromValue($this->status);
    }

    /**
     * Return the urn for the system push notification request
     * @return string
     */
    public function getUrn(): string
    {
        return implode(':', [
            'urn',
            self::URN_METHOD,
            $this->getType(),
            $this->getRequestUuid()
        ]);
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'request_id' => $this->getRequestUuid(),
            'author_guid' => $this->getAuthorGuid(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'link' => $this->getLink(),
            'timestamp' => $this->getCreatedAt(),
            'target' => $this->getTarget(),
            'counter' => $this->getCounter(),
            'urn' => $this->getUrn()
        ];
    }

    public function getGuid(): ?string
    {
        return $this->getAuthorGuid();
    }

    public function getOwnerGuid(): ?string
    {
        return $this->getAuthorGuid();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSubtype(): ?string
    {
        return '';
    }

    public function getAccessId(): string
    {
        return '';
    }
}
