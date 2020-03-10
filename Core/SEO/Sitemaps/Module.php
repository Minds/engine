<?php
/**
 * Module
 *
 * @author Mark
 */

namespace Minds\Core\SEO\Sitemaps;

use Minds\Interfaces\ModuleInterface;

/**
 * Module class
 *
 * @package Minds\Core\SEO\Sitemaps
 */
class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit(): void
    {
        (new Provider())->register();
    }
}
