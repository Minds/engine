<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\Snowplow\Events;

use Minds\Core\Analytics\Snowplow\Enums\SnowplowCheckoutEventTypeEnum;

class SnowplowCheckoutEvent implements SnowplowEventInterface
{
    private array $context;

    public function __construct(
        public readonly SnowplowCheckoutEventTypeEnum $checkoutEventType
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSchema(): string
    {
        return "iglu:com.minds/checkout/jsonschema/1-0-0";
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return array_filter([
            'type' => $this->checkoutEventType->value,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getContext(): ?array
    {
        return array_values($this->context);
    }

    public function setContext(array $contexts = []): self
    {
        $this->context = $contexts;
        return $this;
    }
}
