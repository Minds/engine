<?php
declare(strict_types=1);

namespace Spec\Minds\Core\PWA\Models;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\PWA\Models\TenantManifest;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class TenantManifestSpec extends ObjectBehavior
{
    private string $tenantSiteName = 'Tenant';
    private MultiTenantColorScheme $themeColorScheme = MultiTenantColorScheme::DARK;

    protected Collaborator $config;

    public function let(Config $config): void
    {
        $this->config = $config;

        $this->config->get('site_name')
            ->shouldBeCalled()
            ->willReturn($this->tenantSiteName);

        $this->config->get('theme_override')
            ->shouldBeCalled()
            ->willReturn(['color_scheme' => $this->themeColorScheme]);

        $this->beConstructedWith($this->config);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(TenantManifest::class);
    }

    public function it_should_build_tenant_manifest(): void
    {
        $this->config->get('theme_override')
            ->shouldBeCalled()
            ->willReturn(['color_scheme' => $this->themeColorScheme]);

        $this->beConstructedWith($this->config);

        $this->export()->shouldBe([
            "name" => "Tenant",
            "short_name" => "Tenant",
            "description" => "A social app.",
            "categories" => [
                "social",
                "news",
            ],
            "theme_color" => "#ffffff",
            "background_color" => "#ffffff",
            "display" => "standalone",
            "scope" => "./",
            "start_url" => "/",
            "icons" => [
                [
                    "src" => "/api/v3/multi-tenant/configs/image/square_logo",
                    "type" => "image/png",
                    "sizes" => "192x192"
                ]
            ],
            "prefer_related_applications" => false,
        ]);
    }
}
