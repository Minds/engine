<?php
/**
 * Module
 *
 * @author edgebal
 */

namespace Minds\Core\Classifier;

use Minds\Interfaces\ModuleInterface;

/**
 * Classifier Module
 * @package Minds\Core\Classifier
 */
class Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function onInit()
    {
        (new Provider())->register();
    }
}
