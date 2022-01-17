<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Traits\MagicAttributes;

/**
 * Represents the options for a recommendations algorithm
 * @method int getLimit()
 * @method int setLimit(int $limit)
 * @method string getUserGuid()
 * @method string setUserGuid(string $userGuid)
 * @method string getType()
 * @method string setType(string $type)
 */
class AlgorithmOptions
{
    use MagicAttributes;

    private int $limit = 12;
    private ?string $userGuid = null;
    private string $type = "";

    public function __construct(array $options = [])
    {
        $this->init($options);
    }

    public function init(array $options): self
    {
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
