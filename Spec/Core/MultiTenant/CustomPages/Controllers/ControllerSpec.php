<?php

namespace Spec\Minds\Core\MultiTenant\CustomPages\Controllers;

use Minds\Core\MultiTenant\CustomPages\Controllers\Controller;
use Minds\Core\MultiTenant\CustomPages\Services\Service;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use Minds\Core\MultiTenant\CustomPages\Types\CustomPage;
use PhpSpec\ObjectBehavior;
use Minds\Entities\User;

class ControllerSpec extends ObjectBehavior
{
    public function let(Service $service)
    {
        $this->beConstructedWith($service);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

public function it_should_get_a_custom_page(Service $service)
{
    $pageType = CustomPageTypesEnum::PRIVACY_POLICY;
    $content = 'Sample Content';
    $externalLink = 'https://example.com';
    $tenantId = 1;

    $mockCustomPage = new CustomPage(
        $pageType,
        $content,
        $externalLink,
        $tenantId
    );

    $service->getCustomPageByType($pageType)->willReturn($mockCustomPage);

    $this->getCustomPage($pageType->value)->shouldReturn($mockCustomPage);
}


    public function it_should_set_a_custom_page_with_content(Service $service, User $loggedInUser)
    {
        $pageType = CustomPageTypesEnum::PRIVACY_POLICY;
        $content = "Sample Content";
        $externalLink = null;

        $service->setCustomPage($pageType, $content, $externalLink)->willReturn(true);

        $this->setCustomPage($pageType->value, $content, $externalLink, $loggedInUser)->shouldReturn(true);
    }

    public function it_should_set_a_custom_page_with_external_link(Service $service, User $loggedInUser)
    {
        $pageType = CustomPageTypesEnum::PRIVACY_POLICY;
        $content = null;
        $externalLink = "https://example.com";

        $service->setCustomPage($pageType, $content, $externalLink)->willReturn(true);

        $this->setCustomPage($pageType->value, $content, $externalLink, $loggedInUser)->shouldReturn(true);
    }
}
