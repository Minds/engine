<?php

namespace Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Traits\MagicAttributes;

/**
 * @method setSuccessful(bool $successful)
 * @method bool getSuccessful(bool $successful)
 */
class SnowplowProofOfWorkContext implements SnowplowContextInterface
{
    use MagicAttributes;

    private bool $successful = false;

    public function getSchema(): string
    {
        return "iglu:com.minds/proof_of_work_context/jsonschema/1-0-0";
    }

    public function getData(): array
    {
        return array_filter([
            'proof_of_work' => $this->successful
        ]);
    }
}
