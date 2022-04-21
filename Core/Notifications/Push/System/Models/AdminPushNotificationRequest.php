<?php

namespace Minds\Core\Notifications\Push\System\Models;

use Cassandra\Uuid;
use Minds\Core\Notifications\Push\System\AdminPushNotificationRequestStatus;
use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;
use Minds\Exceptions\ServerErrorException;
use Minds\Traits\MagicAttributes;

/**
 * @method self setRequestId(Uuid $requestId)
 * @method Uuid getRequestId()
 * @method self setAuthorId(string $authorId)
 * @method string getAuthorId()
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
 * @method self setCreatedOn(string $createdOn)
 * @method string getCreatedOn()
 * @method self setStartedOn(string $startedOn)
 * @method string getStartedOn()
 * @method self setCompletedOn(string $completedOn)
 * @method string getCompletedOn()
 */
class AdminPushNotificationRequest implements ExportableInterface, EntityInterface
{
    use MagicAttributes;

    private Uuid $requestId;
    private ?string $authorGuid;
    private string $title;
    private ?string $message;
    private ?string $link;
    private string $target;
    private ?int $counter;
    private int $status;
    private string $createdOn;
    private ?string $startedOn;
    private ?string $completedOn;

    /**
     * @throws ServerErrorException
     */
    public static function fromArray(array $data): self
    {
        $notificationData = new self;

        if (!isset($data['request_id'])) {
            throw new ServerErrorException("Missing property 'request_id' in System Push Notification event");
        }
        if (is_string($data['request_id'])) {
            $data['request_id'] = new Uuid($data['request_id']);
        }
        $notificationData->setRequestId($data['request_id']);

        if (!isset($data['author_guid'])) {
            throw new ServerErrorException("Missing property 'author_guid' in System Push Notification event");
        }
        $notificationData->setTitle($data['author_guid']);

        if (!isset($data['title'])) {
            throw new ServerErrorException("Missing property 'title' in System Push Notification event");
        }
        $notificationData->setTitle($data['title']);

        if (!isset($data['message'])) {
            throw new ServerErrorException("Missing property 'message' in System Push Notification event");
        }
        $notificationData->setMessage($data['message']);

        if (!isset($data['link'])) {
            throw new ServerErrorException("Missing property 'link' in System Push Notification event");
        }
        $notificationData->setLink($data['link']);

        if (!isset($data['timestamp'])) {
            throw new ServerErrorException("Missing property 'timestamp' in System Push Notification event");
        }
        $notificationData->setCreatedOn($data['timestamp']);

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
        return AdminPushNotificationRequestStatus::fromValue($this->status);
    }

    /**
     * Return the urn for the system push notification request
     * @return string
     */
    public function getUrn(): string
    {
        return implode(':', [
            'urn',
            'system-push-notification',
            $this->getRequestId()
        ]);
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'request_id' => $this->getRequestId(),
            'author_guid' => $this->getAuthorId(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'link' => $this->getLink(),
            'timestamp' => $this->getCreatedOn(),
            'target' => $this->getTarget(),
            'counter' => $this->getCounter(),
            'urn' => $this->getUrn()
        ];
    }

    public function getGuid(): ?string
    {
        return $this->getAuthorId();
    }

    public function getOwnerGuid(): ?string
    {
        return $this->getAuthorId();
    }

    public function getType(): string
    {
        return 'system-push-notification';
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
