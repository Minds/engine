<?php
namespace Minds\Core\Nostr;

use JsonSerializable;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method string getId();
 * @method string getPubKey();
 */
class NostrEvent implements ExportableInterface, JsonSerializable
{
    use MagicAttributes;

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
}
