<?php

namespace Minds\Entities;

use Minds\Traits\MagicAttributes;

/**
 * @method string getField()
 * @method self setField(string $field)
 *
 * @method string getMessage()
 * @method self setMessage(string $message)
 */
class ValidationError implements ExportableInterface
{
    use MagicAttributes;

    public function __construct(
        private string $field = "",
        private string $message = ""
    ) {
    }

    public function export(array $extras = []): array
    {
        return [
            "field" => $this->getField(),
            "message" => $this->getMessage()
        ];
    }
}
