<?php
declare(strict_types=1);

namespace Spec\Minds\Core\PWA\Services;

use Minds\Core\Config\Config;
use Minds\Core\PWA\Models\MindsManifest;
use Minds\Core\PWA\Models\TenantManifest;
use Minds\Core\PWA\Services\ManifestService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ManifestServiceSpec extends ObjectBehavior
{
    protected Collaborator $config;

    public function let(Config $config): void
    {
        $this->config = $config;
        $this->beConstructedWith($this->config);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ManifestService::class);
    }

    public function it_should_get_minds_manifest(): void
    {
        $this->config->get('tenant_id')->willReturn(false);
        $this->getManifest()->shouldBeAnInstanceOf(MindsManifest::class);
    }

    public function it_should_get_tenant_manifest(): void
    {
        $this->config->get('tenant_id')->willReturn(true);
        $this->getManifest()->shouldBeAnInstanceOf(TenantManifest::class);
    }
}
