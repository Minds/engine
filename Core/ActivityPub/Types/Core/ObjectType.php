<?php
namespace Minds\Core\ActivityPub\Types\Core;

use DateTime;
use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\AbstractType;
use Minds\Core\ActivityPub\Types\Object\ImageType;

class ObjectType extends AbstractType
{
    #[ExportProperty]
    protected string $type = 'Object';

    #[ExportProperty]
    public string $id;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attributedTo
     */
    #[ExportProperty]
    public string $attributedTo;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
     */
    #[ExportProperty]
    public string $name;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content
     */
    #[ExportProperty]
    public string $content;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-summary
     */
    #[ExportProperty]
    public string $summary;

    /**
     * @param string[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-to
     */
    #[ExportProperty]
    public array $to;

    /**
     * @param string[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-cc
     */
    #[ExportProperty]
    public array $cc;

    /**
     * @param LinkType|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-tag
     */
    #[ExportProperty]
    public array $tag;

    /**
     * @param string|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-url
     */
    #[ExportProperty]
    public string|array $url;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     * @todo Change this to be a model
     */
    #[ExportProperty]
    public ImageType $icon;
    
    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-published
     */
    #[ExportProperty]
    public DateTime $published;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-inreplyto
     */
    #[ExportProperty]
    public string $inReplyTo;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-mediaType
     */
    #[ExportProperty]
    public string $mediaType;

    /**
     * @param ObjectType[]|LinkType[]
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attachment
     */
    #[ExportProperty]
    public array $attachment;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-height
     */
    #[ExportProperty]
    public int $height;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-width
     */
    #[ExportProperty]
    public int $width;
    

    /**
     * Sets the ID (must be a string)
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Returns the ID
     */
    public function getId(): string
    {
        return $this->id;
    }

}
