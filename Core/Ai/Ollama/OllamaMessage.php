<?php
namespace Minds\Core\Ai\Ollama;

use JsonSerializable;

class OllamaMessage implements JsonSerializable
{
    public function __construct(
        public readonly OllamaRoleEnum $role,
        public readonly string $content,
        /** @var string[] */
        public readonly array $images = [],
    ) {
        
    }

    public function jsonSerialize(): array
    {
        $json = [
            'role' => strtolower($this->role->name),
            'content' => $this->content,
        ];

        if ($this->images) {
            $json['images'] = $this->images;
        }

        return $json;
    }
}
