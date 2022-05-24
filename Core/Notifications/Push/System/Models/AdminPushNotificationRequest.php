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
 * @method self setSuccessfulCounter(int $successfulCounter)
 * @method int getSuccessfulCounter()
 * @method self setFailedCounter(int $failedCounter)
 * @method int getFailedCounter()
 * @method self setSkippedCounter(int $skippedCounter)
 * @method int getSkippedCounter()
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
    private ?int $successfulCounter;
    private ?int $failedCounter;
    private ?int $skippedCounter;
    private int $status = 0;
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

        if (!key_exists('successful_counter', $data)) {
            throw new ServerErrorException("Missing property 'successful_counter' in System Push Notification event");
        }
        $notificationData->setSuccessfulCounter($data['successful_counter']);

        if (!key_exists('failed_counter', $data)) {
            throw new ServerErrorException("Missing property 'failed_counter' in System Push Notification event");
        }
        $notificationData->setFailedCounter($data['failed_counter']);

        if (!key_exists('skipped_counter', $data)) {
            throw new ServerErrorException("Missing property 'skipped_counter' in System Push Notification event");
        }
        $notificationData->setSkippedCounter($data['skipped_counter']);

        if (!isset($data['status'])) {
            throw new ServerErrorException("Missing property 'status' in System Push Notification event");
        }
        $notificationData->setStatus($data['status']);

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
            'successful_counter' => $this->getSuccessfulCounter(),
            'failed_counter' => $this->getFailedCounter(),
            'skipped_counter' => $this->getSkippedCounter(),
            'status' => $this->getStatus(),
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
