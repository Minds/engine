<?php
declare(strict_types=1);

namespace Spec\Minds\Core\SEO\Robots\Services;

use Minds\Core\Config;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\SEO\Robots\Services\RobotsFileService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;

class RobotsFileServiceSpec extends ObjectBehavior
{
    private Collaborator $config;
    private ReflectionClass $tenantMockFactory;

    public function let(Config $config)
    {
        $this->beConstructedWith($config);
        $this->config = $config;
        $this->tenantMockFactory = new ReflectionClass(Tenant::class);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RobotsFileService::class);
    }

    public function it_should_get_text_for_permissive_file_for_non_tenant(): void
    {
        $host = 'example.minds.com';

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($host);

        $this->config->get('tenant')->willReturn(null);

        $this->getText($host)->shouldEqual(<<<TXT
        User-agent: *
        Disallow: /api/
        Allow: /api/v3/discovery
        Allow: /api/v3/newsfeed/activity/og-image/
    
        Sitemap: {$host}sitemap.xml
        TXT);
    }

    public function it_should_get_text_for_permissive_file_for_tenant_with_domain(): void
    {
        $host = 'example.minds.com';
        $tenant = $this->tenantMockFactory->newInstanceWithoutConstructor();
        $this->tenantMockFactory->getProperty('domain')->setValue($tenant, 'example.minds.com');

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($host);

        $this->config->get('tenant')->willReturn($tenant);

        $this->getText($host)->shouldEqual(<<<TXT
        User-agent: *
        Disallow: /api/
        Allow: /api/v3/discovery
        Allow: /api/v3/newsfeed/activity/og-image/
    
        Sitemap: {$host}sitemap.xml
        TXT);
    }

    public function it_should_get_text_for_deny_all_file_for_minds_io(): void
    {
        $host = 'example.minds.io';

        $this->config->get('site_url')
            ->shouldNotBeCalled();

        $this->config->get('tenant')->willReturn(null);

        $this->getText($host)->shouldEqual(<<<TXT
        User-agent: Twitterbot
        Disallow:
        User-agent: *
        Disallow: /
        TXT);
    }

    public function it_should_get_text_for_deny_all_file_for_tenant_without_domain(): void
    {
        $host = 'example.minds.com';
        $tenant = $this->tenantMockFactory->newInstanceWithoutConstructor();
        $this->tenantMockFactory->getProperty('domain')->setValue($tenant, null);

        $this->config->get('site_url')
            ->shouldNotBeCalled();

        $this->config->get('tenant')->willReturn($tenant);

        $this->getText($host)->shouldEqual(<<<TXT
        User-agent: Twitterbot
        Disallow:
        User-agent: *
        Disallow: /
        TXT);
    }
}
