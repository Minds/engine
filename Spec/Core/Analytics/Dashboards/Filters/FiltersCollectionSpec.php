<?php

namespace Spec\Minds\Core\Analytics\Dashboards\Filters;

use Minds\Core\Analytics\Dashboards\Filters\FiltersCollection;
use Minds\Core\Analytics\Dashboards\Filters\ViewsFilter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FiltersCollectionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(FiltersCollection::class);
    }

    public function it_should_add_filters_to_collection()
    {
        $this->addFilters(new ViewsFilter);
        $filters = $this->getFilters();
        $filters['views']->getId()
            ->shouldBe('views');
    }

    public function it_should_export_filters()
    {
        $this->addFilters(new ViewsFilter);
        $export = $this->export();
        $export[0]['id']
            ->shouldBe('views');
        $export[0]['options']
            ->shouldHaveCount(4);
    }
}
