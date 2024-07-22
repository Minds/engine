<?php
namespace Minds\Integrations\Bloomerang\Models;

class BloomerangGroup
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
    ) {

    }

    public static function buildFromArray(array $data): BloomerangGroup
    {
        return new BloomerangGroup(
            id: $data['Id'],
            name: $data['Name'],
            description: $data['Description'],
        );
    }
}
