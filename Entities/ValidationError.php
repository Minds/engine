<?php

namespace Minds\Entities;

/**
 * @method string getField()
 * @method self setField(string $field)
 *
 * @method string getMessage()
 * @method self setMessage(string $message)
 */
class ValidationError implements ExportableInterface
{
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
