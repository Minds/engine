<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

class ChannelFilter extends AbstractFilter
{
    /** @var string */
    protected $id = "channel";

    /** @var string */
    protected $label = "Channel";

    /** @var array */
    protected $permissions = [ 'admin' ];

    /** @var string */
    protected $description = "Filter by channels or by the full site";

    /** @var string */
    protected $selectedOption = "all";

    public function __construct()
    {
        $this->options = (new FilterOptions())
            ->setOptions(
                (new FilterOptionsOption())
                    ->setId("all")
                    ->setLabel("All")
                    ->setDescription("Global, site-wide metrics"),
                (new FilterOptionsOption())
                    ->setId("self")
                    ->setLabel("Me")
                    ->setDescription("Your currently logged in user"),
                (new FilterOptionsOption())
                    ->setId("custom")
                    ->setLabel("Custom (Search)")
                    ->setDescription("Search for a channel to view their metrics")
            );
    }
}
