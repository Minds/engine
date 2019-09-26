<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

class PlatformFilter extends AbstractFilter
{
    /** @var string */
    protected $id = "platform";

    /** @var string */
    protected $label = "Platform types";

    public function __construct()
    {
        $this->options = (new FilterOptions())
            ->setOptions(
                (new FilterOptionsOption())
                    ->setId("all")
                    ->setLabel("All"),
                (new FilterOptionsOption())
                    ->setId("browser")
                    ->setLabel("Browser"),
                (new FilterOptionsOption())
                    ->setId("mobile")
                    ->setLabel("Mobile")
            );
    }
}
