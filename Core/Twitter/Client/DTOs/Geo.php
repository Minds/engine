<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Client\DTOs;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setPlaceId(string $placeId)
 * @method string getPlaceId()
 */
class Geo implements ExportableInterface
{
    use MagicAttributes;
    
    private string $placeId;

    public function export(array $extras = []): array
    {
        return [
            'place_id' => $this->getPlaceId()
        ];
    }
}
