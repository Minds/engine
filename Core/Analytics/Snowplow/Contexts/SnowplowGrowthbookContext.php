<?php
namespace Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Traits\MagicAttributes;

/**
 * @method SnowplowActionEvent setExperimentId(strig $experimentId)
 * @method SnowplowActionEvent setVariationId(int $variationId)
 */
class SnowplowGrowthbookContext implements SnowplowContextInterface
{
    use MagicAttributes;

    /** @var string */
    protected $experimentId;

    /** @var int */
    protected $variationId;

    /**
     * Returns the schema
     */
    public function getSchema(): string
    {
        return "iglu:com.minds/growthbook_context/jsonschema/1-0-1";
    }

    /**
     * Returns the sanitized data
     * null values are removed
     * @return array
     */
    public function getData(): array
    {
        return array_filter([
            'experiment_id' => (string) $this->experimentId,
            'variation_id' => (int) $this->variationId,
        ]);
    }
}
