<?php
/**
 * Ref.
 * Holds a forward reference to a provider and a method.
 * Used by PSR-7 Router to resolve DI bindings.
 *
 * @author edgebal
 */

namespace Minds\Core\Di;

use Minds\Traits\MagicAttributes;

/**
 * Class Ref
 * @package Minds\Core\Di
 * @method string getProvider()
 * @method Ref setProvider(string $provider)
 * @method string getMethod()
 * @method Ref setMethod(string $method)
 */
class Ref
{
    use MagicAttributes;

    /** @var string */
    protected $provider;

    /** @var string */
    protected $method;

    /**
     * Ref constructor.
     * @param string $provider
     * @param string $method
     */
    public function __construct(string $provider, string $method)
    {
        $this->setProvider($provider);
        $this->setMethod($method);
    }

    /**
     * @param string $provider
     * @param string $method
     * @return Ref
     */
    public static function _(string $provider, string $method): Ref
    {
        return new Ref($provider, $method);
    }
}
