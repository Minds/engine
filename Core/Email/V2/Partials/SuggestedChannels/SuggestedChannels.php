<?php

namespace Minds\Core\Email\V2\Partials\SuggestedChannels;

use Minds\Core\Email\V2\Common\Template;
use Minds\Traits\MagicAttributes;

class SuggestedChannels extends Template
{
    use MagicAttributes;

    protected $tracking;
    protected $suggestions;

    public function build()
    {
        $this->loadFromFile = false;
        $this->setTemplate('./template.tpl');
        $this->set('tracking', $this->tracking);
        $this->set('suggestions', $this->suggestions);
        if ($this->suggestions) {
            return $this->render();
        }
    }
}
