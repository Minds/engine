<?php

namespace Minds\Core\Recommendations;

use Minds\Traits\MagicAttributes;

class RepositoryOptions
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
}
