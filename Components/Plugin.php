<?php
/**
 * A base object for plugins
 * 
 * 
 * @todo this is a work in progress and will replace the ElggPlugin object
 */
 
namespace Minds\Components;

class Plugin extends \ElggPlugin
{
    public function start($flags = null)
    {
        //only legacy plugins use the start function
        $this->registerViews();
        $this->registerLanguages();
    }
    
    public function init()
    {
    }
}
