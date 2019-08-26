<?php
/**
* Thrid Party Social Network Factory
*/
namespace Minds\Core\ThirdPartyNetworks;

class Factory
{
    /**
     * Build the handler
     * @param string $handler
     * @param array $options (optional)
     * @return BoostHandlerInterface
     */
    public static function build($handler, $options = [])
    {
        $handler = ucfirst($handler);
        $handler = "Minds\\Core\\ThirdPartyNetworks\\Networks\\$handler";
        if (class_exists($handler)) {
            $class = new $handler($options);
            if ($class instanceof Networks\NetworkInterface) {
                return $class;
            }
        }
        throw new \Exception("Social Network not found");
    }
}
