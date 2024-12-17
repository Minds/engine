<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Types;

use Minds\Core\Chat\Entities\ChatImage;
use Minds\Core\Di\Di;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Chat image node.
 */
#[Type]
class ChatImageNode implements NodeInterface
{
    public function __construct(
        public readonly ChatImage $chatImage
    ) {
    }

    /**
     * The unique ID of the image for GraphQL.
     * @return ID The unique ID of the image.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID('chat-image-' . $this->getGuid());
    }

    /**
     * The guid of the image.
     * @return string The guid of the image.
     */
    #[Field]
    public function getGuid(): string
    {
        return (string) $this->chatImage->guid;
    }

    /**
     * The URL of the image.
     * @return string The URL of the image.
     */
    #[Field]
    public function getUrl(): string
    {
        return $this->chatImage->getUrl();
    }

    /**
     * The width of the image.
     * @return int|null The width of the image.
     */
    #[Field]
    public function getWidth(): ?int
    {
        return $this->chatImage->width;
    }

    /**
     * The height of the image.
     * @return int|null The height of the image.
     */
    #[Field]
    public function getHeight(): ?int
    {
        return $this->chatImage->height;
    }

    /**
     * The blurhash of the image.
     * @return string|null The blurhash of the image.
     */
    #[Field]
    public function getBlurhash(): ?string
    {
        return $this->chatImage->blurhash;
    }

    /**
     * The created timestamp of the image in ISO 8601 format.
     * @return string|null The created timestamp of the image in ISO 8601 format.
     */
    #[Field]
    public function getCreatedTimestampISO8601(): ?string
    {
        return $this->chatImage->createdTimestamp->format('c');
    }

    /**
     * The created timestamp of the image in Unix format.
     * @return string|null The created timestamp of the image in Unix format.
     */
    #[Field]
    public function getCreatedTimestampUnix(): ?string
    {
        return $this->chatImage->createdTimestamp->format('U');
    }

    /**
     * The updated timestamp of the image in ISO 8601 format.
     * @return string|null The updated timestamp of the image in ISO 8601 format.
     */
    #[Field]
    public function getUpdatedTimestampISO8601(): ?string
    {
        return $this->chatImage->updatedTimestamp->format('c');
    }

    /**
     * The updated timestamp of the image in Unix format.
     * @return string|null The updated timestamp of the image in Unix format.
     */
    #[Field]
    public function getUpdatedTimestampUnix(): ?string
    {
        return $this->chatImage->updatedTimestamp->format('U');
    }
}
