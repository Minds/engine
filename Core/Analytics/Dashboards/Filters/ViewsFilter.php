<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

class ViewsFilter extends FilterAbstract
{
    /** @var string */
    protected $id = "views";

    /** @var string */
    protected $label = "View types";

    public function __construct()
    {
        $this->options = (new FilterOptions())
            ->setOptions(
                (new FilterOptionsOption())
                    ->setId("total")
                    ->setLabel("Total"),
                (new FilterOptionsOption())
                    ->setId("organic")
                    ->setLabel("Organic"),
                (new FilterOptionsOption())
                    ->setId("boosted")
                    ->setLabel("Boosted"),
                (new FilterOptionsOption())
                    ->setId("single")
                    ->setLabel("Single")
            );
    }
}
