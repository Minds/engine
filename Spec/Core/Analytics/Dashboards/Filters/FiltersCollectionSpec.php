<?php

namespace Spec\Minds\Core\Analytics\Dashboards\Filters;

use Minds\Entities\User;
use Minds\Core\Analytics\Dashboards\Filters\FiltersCollection;
use Minds\Core\Analytics\Dashboards\Filters\ViewTypeFilter;
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
        $this->setUser(new User());
        $this->addFilters(new ViewTypeFilter);
        $filters = $this->getFilters();
        $filters['view_type']->getId()
            ->shouldBe('view_type');
    }

    public function it_should_export_filters()
    {
        $this->setUser(new User());
        $this->addFilters(new ViewTypeFilter);
        $export = $this->export();
        $export[0]['id']
            ->shouldBe('view_type');
        $export[0]['options']
            ->shouldHaveCount(4);
    }
}
