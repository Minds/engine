<?php
declare(strict_types=1);

namespace Minds\Core\SEO\Robots\Services;

use Minds\Core\Config\Config;
use Zend\Diactoros\Response\TextResponse;

/**
 * Service for generation of robots.txt file.
 */
class RobotsFileService
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Get / generate the robots.txt file.
     * @param string $host - host to get file for.
     * @return TextResponse - text response to be returned to front-end.
     */
    public function getText(string $host): string
    {
        $tenant = $this->config->get('tenant');

        if(
            str_contains($host, 'minds.io') ||
            ($tenant && !isset($tenant?->domain))
        ) {
            return $this->denyAllRobotsTxt();
        }

        return $this->getPermissiveRobotsTxt();
    }

    /**
     * Get permissive robots txt.
     * @return string permissive robots txt file.
     */
    private function getPermissiveRobotsTxt(): string
    {
        $siteUrl = $this->config->get('site_url');
        return
            <<<TXT
            User-agent: *
            Disallow: /api/
            Allow: /api/v3/discovery
            Allow: /api/v3/newsfeed/activity/og-image/
        
            Sitemap: {$siteUrl}sitemap.xml
            TXT;
    }

    /**
     * Deny all robots txt.
     * @return string deny all robots txt.
     */
    private function denyAllRobotsTxt(): string
    {
        return
            <<<TXT
            User-agent: Twitterbot
            Disallow:
            User-agent: *
            Disallow: /
            TXT;
    }
}
