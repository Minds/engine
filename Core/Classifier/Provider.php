<?php
/**
 * Provider
 *
 * @author edgebal
 */

namespace Minds\Core\Classifier;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Classifier DI Provider
 * @package Minds\Core\Classifier
 */
class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind('Classifier', function () {
            return new Manager();
        });
    }
}
