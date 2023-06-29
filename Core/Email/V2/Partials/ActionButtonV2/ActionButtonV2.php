<?php

namespace Minds\Core\Email\V2\Partials\ActionButtonV2;

use Minds\Core\Email\V2\Common\Template;
use Minds\Traits\MagicAttributes;

/**
 * @method self setLabel(string $label)
 * @method self setPath(string $path)
 */
class ActionButtonV2 extends Template
{
    use MagicAttributes;

    /** @var string */
    protected string $path;

    /** @var string */
    protected string $label;


    /** Build button
     * @return string
     */
    public function build()
    {
        $this->loadFromFile = false;
        $this->setTemplate('./template.tpl');

        $siteUrl = $this->data['site_url'];

        if (strpos($this->path, 'http', 0) === 0) {
            $href = $this->path;
        } else {
            $href = $siteUrl . $this->path;
        }

        $this->set('href', $href);
        $this->set('label', $this->label);

        return $this->render();
    }
}
