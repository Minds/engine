<?php
namespace Minds\Core\Email\Campaigns;

class Factory
{
    public static function build($name)
    {
        $name = ucfirst($name);
        $class = "Minds\\Core\\Email\\Campaigns\\$name";
        if (class_exists($class)) {
            return new $class;
        }
    }
}
