<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;

class InputTypesProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(InputTypesFactory::class, function (): InputTypesFactory {
            return new InputTypesFactory();
        });
    }
}
