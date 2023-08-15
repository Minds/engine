<?php
namespace Minds\Core\ActivityPub\Types\Core;

use DateTime;
use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\AbstractType;

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
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-url
     */
    #[ExportProperty]
    public string $url;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     * @todo Change this to be a model
     */
    #[ExportProperty]
    public array $icon;
    
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
