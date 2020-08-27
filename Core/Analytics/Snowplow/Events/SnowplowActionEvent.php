<?php
namespace Minds\Core\Analytics\Snowplow\Events;

use Minds\Traits\MagicAttributes;
use Minds\Core\Analytics\Snowplow\Contexts\SnowplowContextInterface;

/**
 * @method SnowplowActionEvent setAction(string $action)
 * @method SnowplowActionEvent setCommentGuid(string $commentGuid)
 * @method SnowplowActionEvent setBoostRating(int $boostRating)
 * @method SnowplowActionEvent setBoostRejectReason(int $boostRejectReason)
 */
class SnowplowActionEvent implements SnowplowEventInterface
{
    use MagicAttributes;

    /** @var string */
    protected $action;

    /** @var string */
    protected $commentGuid;

    // TODO: move boost actions to their own events

    /** @var int */
    protected $boostRating;

    /** @var int */
    protected $boostRejectReason;

    /** @var SnowplowContextInterface[] */
    protected $context = [];

    /**
     * Returns the schema
     */
    public function getSchema(): string
    {
        return "iglu:com.minds/action/jsonschema/1-0-0";
    }

    /**
     * Returns the sanitized data
     * null values are removed
     * @return array
     */
    public function getData(): array
    {
        return array_filter([
            'action' => $this->action,
            'comment_guid' => $this->commentGuid,
            'boost_rating' => $this->boostRating,
            'boost_reject_reason' => $this->boostRejectReason,
        ]);
    }

    /**
     * Sets the contexts
     * @param SnowplowContextInterface[] $contexts
     */
    public function setContext(array $context = []): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Returns attached contexts
     * @return array
     */
    public function getContext(): ?array
    {
        return array_values($this->context);
    }
}
