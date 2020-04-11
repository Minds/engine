<?php

namespace Minds\Core\Email\V2\Partials\ActionButton;

use Minds\Core\Email\V2\Common\Template;
use Minds\Traits\MagicAttributes;

class ActionButton extends Template
{
    use MagicAttributes;

    protected $path;
    protected $label;

    public function build()
    {
        $this->loadFromFile = false;
        $this->setTemplate('./template.tpl');
        $this->set('path', $this->path);
        $this->set('label', $this->label);
            return $this->render();
    }
}

