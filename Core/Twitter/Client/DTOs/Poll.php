<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Client\DTOs;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setOptions(array $options)
 * @method array getOptions()
 * @method self setDurationMinutes(int $durationMinutes)
 * @method int getDurationMinutes()
 */
class Poll implements ExportableInterface
{
    use MagicAttributes;

    private array $options;
    private int $durationMinutes;

    public function export(array $extras = []): array
    {
        return [
            'duration_minutes' => $this->getDurationMinutes(),
            'options' => $this->getOptions()
        ];
    }
}
