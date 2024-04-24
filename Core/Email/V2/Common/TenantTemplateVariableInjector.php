<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2\Common;

use Minds\Core\Config\Config;

/**
 * Inject tenant specific variables into a given template.
 * Intended for use with the default.v2.tpl template.
 */
class TenantTemplateVariableInjector
{
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Inject tenant specific variables into a given template.
     * @param Template $template - template to inject variables into.
     * @return Template - template with injected variables.
     */
    public function inject(Template $template): Template
    {
        $siteName = $this->config->get('site_name') ?? 'Minds';
        $template->set('site_name', $siteName);
        $template->set('copyright_text', $siteName . " &#169; " . date("Y"));

        if ($siteUrl = $this->config->get('site_url')) {
            $template->set('site_url', $siteUrl);
            $template->set('logo_url', $siteUrl . 'api/v3/multi-tenant/configs/image/square_logo');
        }

        $themeOverride = $this->config->get('theme_override');

        if (is_array($themeOverride) && $themeOverride['color_scheme']) {
            $template->set('color_scheme', $themeOverride['color_scheme']);
        }

        return $template;
    }
}
