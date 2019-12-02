<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

class PlatformFilter extends AbstractFilter
{
    /** @var string */
    protected $id = "platform";

    /** @var string */
    protected $label = "Platform";

    /** @var string */
    protected $description = "Filter by device types";

    public function __construct()
    {
        $this->options = (new FilterOptions())
            ->setOptions(
                (new FilterOptionsOption())
                    ->setId("all")
                    ->setLabel("All")
                    ->setDescription("Browsers, Mobile and APIs"),
                (new FilterOptionsOption())
                    ->setId("browser")
                    ->setLabel("Browser")
                    ->setDescription("Browsers"),
                (new FilterOptionsOption())
                    ->setId("mobile")
                    ->setLabel("Mobile")
                    ->setDescription("Native mobile applications")
            );
    }
}
