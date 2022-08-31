<?php

namespace Minds\Core\Nostr;

use JsonSerializable;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setId(string $id);
 * @method string getId();
 * @method self setPubKey(string $pubKey)
 * @method string getPubKey();
 */
class NostrEvent implements ExportableInterface, JsonSerializable
{
    use MagicAttributes;

    /** @var int */
    const EVENT_KIND_0 = 0; // set_metadata

    /** @var int */
    const EVENT_KIND_1 = 1; // text_note

    /** @var int */
    const EVENT_KIND_2 = 2; // recommend_server

    /** @var int */
    const EVENT_KIND_9 = 9; // delete

    protected string $id;
    protected string $pubKey;
    protected int $created_at;
    protected int $kind;
    protected array $tags = [];
    protected string $content;
    protected string $sig;

    /**
     * @inheritDoc
     */
    public function export(array $extras = []): array
    {
        return  [
            'id' => $this->id,
            'pubkey' => $this->pubKey,
            'created_at' => $this->created_at,
            'kind' => $this->kind,
            'tags' => $this->tags,
            'content' => $this->content,
            'sig' => $this->sig,
        ];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return json_encode($this->export());
    }

    /**
     * A factor to build nostr event from array
     * @param array $array
     * @return NostrEvent
     */
    public static function buildFromArray(array $array): NostrEvent
    {
        $nostrEvent = new NostrEvent();
        $nostrEvent->setId($array['id'])
            ->setPubKey($array['pubkey'])
            ->setCreated_at($array['created_at'])
            ->setKind($array['kind'])
            ->setTags($array['tags'])
            ->setContent($array['content'])
            ->setSig($array['sig']);

        return $nostrEvent;
    }
}
