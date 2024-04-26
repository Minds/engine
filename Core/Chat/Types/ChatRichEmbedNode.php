<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Types;

use Minds\Core\Chat\Entities\ChatRichEmbed;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Chat rich embed node
 */
#[Type]
class ChatRichEmbedNode implements NodeInterface
{
    public function __construct(
        public readonly ChatRichEmbed $chatRichEmbed
    ) {
    }

    /**
     * The unique ID of the rich embed for GraphQL.
     * @return ID The unique ID of the rich embed.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID('rich-embed-' . md5($this->getUrl() . $this->getUpdatedTimestampUnix()));
    }

    /**
     * The URL of the rich embed.
     * @return string The URL of the rich embed.
     */
    #[Field]
    public function getUrl(): string
    {
        return $this->chatRichEmbed->url;
    }

    /**
     * The canonical URL of the rich embed.
     * @return string The canonical URL of the rich embed.
     */
    #[Field]
    public function getCanonicalUrl(): string
    {
        return $this->chatRichEmbed->canonicalUrl;
    }

    /**
     * The title of the rich embed.
     * @return string|null The title of the rich embed.
     */
    #[Field]
    public function getTitle(): ?string
    {
        return $this->chatRichEmbed->title;
    }

    /**
     * The description of the rich embed.
     * @return string|null The description of the rich embed.
     */
    #[Field]
    public function getDescription(): ?string
    {
        return $this->chatRichEmbed->description;
    }

    /**
     * The author of the rich embed.
     * @return string|null The author of the rich embed.
     */
    #[Field]
    public function getAuthor(): ?string
    {
        return $this->chatRichEmbed->author;
    }

    /**
     * The thumbnail src of the rich embed.
     * @return string|null The thumbnail src of the rich embed.
     */
    #[Field]
    public function getThumbnailSrc(): ?string
    {
        return $this->chatRichEmbed->thumbnailSrc;
    }

    /**
     * The created timestamp of the rich embed in ISO 8601 format.
     * @return string|null The created timestamp of the rich embed in ISO 8601 format.
     */
    #[Field]
    public function getCreatedTimestampISO8601(): ?string
    {
        return $this->chatRichEmbed->createdTimestamp->format('c');
    }

    /**
     * The created timestamp of the rich embed in Unix format.
     * @return string|null The created timestamp of the rich embed in Unix format.
     */
    #[Field]
    public function getCreatedTimestampUnix(): ?string
    {
        return $this->chatRichEmbed->createdTimestamp->format('U');
    }

    /**
     * The updated timestamp of the rich embed in ISO 8601 format.
     * @return string|null The updated timestamp of the rich embed in ISO 8601 format.
     */
    #[Field]
    public function getUpdatedTimestampISO8601(): ?string
    {
        return $this->chatRichEmbed->updatedTimestamp->format('c');
    }

    /**
     * The updated timestamp of the rich embed in Unix format.
     * @return string|null The updated timestamp of the rich embed in Unix format.
     */
    #[Field]
    public function getUpdatedTimestampUnix(): ?string
    {
        return $this->chatRichEmbed->updatedTimestamp->format('U');
    }
}
