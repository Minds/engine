<?php
namespace Minds\Core\Analytics\Snowplow\Events;

use Minds\Traits\MagicAttributes;
use Minds\Core\Analytics\Snowplow\Contexts\SnowplowContextInterface;

/**
 * Growthbook event for snowplow consumption.
 */
class SnowplowGrowthbookEvent implements SnowplowEventInterface
{
    use MagicAttributes;

    /** @var string */
    protected $experimentId;

    /** @var int */
    protected $variationId;

    /** @var SnowplowContextInterface[] */
    protected $context = [];

    /**
     * Returns the schema.
     * @return string pointer to schema.
     */
    public function getSchema(): string
    {
        return 'iglu:com.minds/growthbook_experiment/jsonschema/1-0-0';
    }

    /**
     * Returns the sanitized data.
     * @return array - the data.
     */
    public function getData(): array
    {
        return [
            'experiment_id' => $this->getExperimentId(),
            'variation_id' => $this->getVariationId()
        ];
    }

    /**
     * Sets the contexts.
     * @param SnowplowContextInterface[] $contexts.
     * @return self - instance of this.
     */
    public function setContext(array $context = []): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Returns attached contexts.
     * @return array array of instances contexts.
     */
    public function getContext(): ?array
    {
        return array_values($this->context);
    }

    /**
     * Sets the experiment ID of the event
     * @param string $experimentId - experiment id key to set
     * @return self - instance of $this.
     */
    public function setExperimentId(string $experimentId): self
    {
        $this->experimentId = $experimentId;
        return $this;
    }

    /**
     * Gets experiment ID of the event.
     * @return string - experiment id of the event.
     */
    public function getExperimentId(): string
    {
        return $this->experimentId;
    }

    /**
     * Sets variation id of the event.
     * @param int $variationId - variation id to set.
     * @return self - instance of $this.
     */
    public function setVariationId(int $variationId): self
    {
        $this->variationId = $variationId;
        return $this;
    }

    /**
     * Gets variation id of the event.
     * @return int variation id of the event.
     */
    public function getVariationId(): int
    {
        return $this->variationId;
    }
}
