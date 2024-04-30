<?php

namespace Spec\Minds\Core\Email\V2\Common;

use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class TenantTemplateVariableInjectorSpec extends ObjectBehavior
{
    private Collaborator $config;

    public function let(
        Config $config,
    ) {
        $this->beConstructedWith($config);
        $this->config = $config;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(TenantTemplateVariableInjector::class);
    }

    public function it_should_inject_all_template_variables(Template $template)
    {
        $siteName = 'Minds';
        $siteUrl = 'https://exampleTenant.minds.io/';
        $themeOverride = [
            'color_scheme' => 'DARK'
        ];

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->config->get('theme_override')
            ->shouldBeCalled()
            ->willReturn($themeOverride);

        $template->set('site_name', $siteName)
            ->shouldBeCalled();

        $template->set('copyright_text', $siteName . " &#169; " . date("Y"))
            ->shouldBeCalled();

        $template->set('site_url', $siteUrl)
            ->shouldBeCalled();

        $template->set('logo_url', $siteUrl . 'api/v3/multi-tenant/configs/image/square_logo')
            ->shouldBeCalled();

        $template->set('color_scheme', $themeOverride['color_scheme'])
            ->shouldBeCalled();

        $this->inject($template)->shouldBeAnInstanceOf(Template::class);
    }

    public function it_should_inject_only_copyright_notice_when_appropriate(Template $template)
    {
        $siteName = 'Minds';
        $siteUrl = 'https://exampleTenant.minds.io/';
        $themeOverride = null;

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->config->get('theme_override')
            ->shouldBeCalled()
            ->willReturn($themeOverride);

        $template->set('site_name', $siteName)
            ->shouldBeCalled();

        $template->set('copyright_text', $siteName . " &#169; " . date("Y"))
            ->shouldBeCalled();

        $template->set('site_url', $siteUrl)
            ->shouldBeCalled();

        $template->set('logo_url', $siteUrl . 'api/v3/multi-tenant/configs/image/square_logo')
            ->shouldBeCalled();

        $template->set('color_scheme', Argument::any())
            ->shouldNotBeCalled();

        $this->inject($template)->shouldBeAnInstanceOf(Template::class);
    }

    public function it_should_inject_only_copyright_notice_and_image_when_appropriate(Template $template)
    {
        $siteName = 'Minds';
        $siteUrl = null;
        $themeOverride = null;

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->config->get('theme_override')
            ->shouldBeCalled()
            ->willReturn($themeOverride);

        $template->set('site_name', $siteName)
            ->shouldBeCalled();

        $template->set('copyright_text', $siteName . " &#169; " . date("Y"))
            ->shouldBeCalled();

        $template->set('logo_url', Argument::any())
            ->shouldNotBeCalled();

        $template->set('color_scheme', Argument::any())
            ->shouldNotBeCalled();

        $this->inject($template)->shouldBeAnInstanceOf(Template::class);
    }

    public function it_should_inject_only_copyright_notice_and_theme_override_when_appropriate(Template $template)
    {
        $siteName = 'Minds';
        $siteUrl = null;
        $themeOverride = [
            'color_scheme' => 'DARK'
        ];

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->config->get('theme_override')
            ->shouldBeCalled()
            ->willReturn($themeOverride);

        $template->set('site_name', $siteName)
            ->shouldBeCalled();

        $template->set('copyright_text', $siteName . " &#169; " . date("Y"))
            ->shouldBeCalled();

        $template->set('logo_url', Argument::any())
            ->shouldNotBeCalled();

        $template->set('color_scheme', 'DARK')
            ->shouldBeCalled();

        $this->inject($template)->shouldBeAnInstanceOf(Template::class);
    }
}
