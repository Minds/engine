<?php

namespace Minds\Core\Email\V2\Partials\ProHeader;

use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\EmailStyles;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\I18n\Translator;
use Minds\Core\Markdown\Markdown;
use Minds\Traits\MagicAttributes;

/**
 * @method ProHeader setProSettings(Pro\Settings $proSettings)
 */
class ProHeader extends Template
{
    use MagicAttributes;

    /** @var Pro\Settings */
    protected $proSettings;

    /** Build button
     * @return string
     */
    public function build()
    {
        $this->loadFromFile = false;
        $this->setTemplate('./template.tpl');

        $this->set('logo_uri', $this->proSettings->getLogoImage());
        $this->set('site_url', "https://{$this->proSettings->getDomain()}/");

        return $this->render();
    }
}
