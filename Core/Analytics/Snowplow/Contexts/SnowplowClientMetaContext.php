<?php

namespace Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Traits\MagicAttributes;

class SnowplowClientMetaContext implements SnowplowContextInterface
{
    use MagicAttributes;

    public string $platform = "";
    public string $source = "";
    public int $timestamp = 0;
    public string $salt = "";
    public string $medium = "";
    public string $campaign = "";
    public string $page_token = "";
    public int $delta = 0;
    public int $position = 0;
    public string $served_by_guid = "";
    /**
     * @inheritDoc
     */
    public function getSchema(): string
    {
        return "iglu:com.minds/client_meta/jsonschema/1-0-0";
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return array_filter([
            'platform' => $this->platform,
            'source' => $this->source,
            'timestamp' => $this->timestamp,
            'salt' => $this->salt,
            'medium' => $this->medium,
            'campaign' => $this->campaign,
            'page_token' => $this->page_token,
            'delta' => $this->delta,
            'position' => $this->position,
            'served_by_guid' => $this->served_by_guid,
        ]);
    }
}
