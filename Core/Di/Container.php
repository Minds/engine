<?php
namespace Minds\Core\Di;

use Minds\Core\Di\Di;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 Wrapper for the DI
 */
class Container implements ContainerInterface
{
    /** @var Di */
    private $di;

    public function __construct(Di $di = null)
    {
        $this->di ??= $di;
    }

    public function get($id): mixed
    {
        return $this->di->get($id);
    }

    public function has($id): bool
    {
        return $this->di->has($id);
    }
}
