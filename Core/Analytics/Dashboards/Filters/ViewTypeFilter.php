<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

class ViewTypeFilter extends AbstractFilter
{
    /** @var string */
    protected $id = "view_type";

    /** @var string */
    protected $label = "View types";

    /** @var string */
    protected $description = "Filter by the breakdown of views";

    public function __construct()
    {
        $this->options = (new FilterOptions())
            ->setOptions(
                (new FilterOptionsOption())
                    ->setId("total")
                    ->setLabel("Total")
                    ->setDescription("All views recorded on assets"),
                (new FilterOptionsOption())
                    ->setId("organic")
                    ->setLabel("Organic")
                    ->setDescription("Views on assets that excludes boosted impressions"),
                (new FilterOptionsOption())
                    ->setId("boosted")
                    ->setLabel("Boosted")
                    ->setDescription("Views recorded on assets that were boosted"),
                (new FilterOptionsOption())
                     ->setId("single")
                     ->setLabel("Pageview")
                     ->setDecription("Views recorded on single pages, not in feeds")
            );
    }
}
