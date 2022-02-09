<?php

namespace Minds\Entities;

use Minds\Traits\MagicAttributes;

/**
 * Represents the options for a recommendations repository
 * @method int getLimit()
 * @method int setLimit(int $limit)
 * @method int getOffset()
 * @method int setOffset(int $offset)
 * @method string getUserGuid()
 * @method string setUserGuid(string $userGuid)
 * @method string getType()
 * @method string setType(string $type)
 */
class ElasticSearchRepositoryOptions
{
    use MagicAttributes;

    private int $limit = 12;
    private int $offset = 0;
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
