<?php

namespace Spec\Minds\Core\MultiTenant\CustomPages\Services;

use Minds\Core\MultiTenant\CustomPages\Services\Service;
use Minds\Core\MultiTenant\CustomPages\Repository;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use Minds\Core\MultiTenant\CustomPages\Types\CustomPage;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ServiceSpec extends ObjectBehavior
{
    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Service::class);
    }

    public function it_should_get_a_custom_page(Repository $repository)
    {
        $pageType = CustomPageTypesEnum::PRIVACY_POLICY;
        $customPage = new CustomPage($pageType, 'Sample Content', null, 'Default content', 1);

        $repository->getCustomPageByType($pageType)->willReturn($customPage);

        $this->getCustomPageByType($pageType)->shouldReturn($customPage);
    }

    public function it_should_set_a_custom_page_with_content(Repository $repository)
    {
        $pageType = CustomPageTypesEnum::PRIVACY_POLICY;
        $content = "Sample Content";
        $externalLink = null;

        $repository->setCustomPage($pageType, $content, $externalLink)->willReturn(true);

        $this->setCustomPage($pageType, $content, $externalLink)->shouldReturn(true);
    }

    public function it_should_set_a_custom_page_with_external_link(Repository $repository)
    {
        $pageType = CustomPageTypesEnum::PRIVACY_POLICY;
        $content = null;
        $externalLink = "https://example.com";

        $repository->setCustomPage($pageType, $content, $externalLink)->willReturn(true);

        $this->setCustomPage($pageType, $content, $externalLink)->shouldReturn(true);
    }
}
