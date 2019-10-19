<?php
namespace Minds\Entities\Object;

use Minds\Entities;
use Minds\Core;

/**
 * Carousel Entity
 */
class Carousel extends Entities\Object
{
    /**
     * Initialize entity attributes
     * @return void
     */
    public function initializeAttributes()
    {
        parent::initializeAttributes();
        $this->attributes = array_merge($this->attributes, [
            'owner_guid' => Core\Session::getLoggedInUserGuid(),
            'access_id' => 2,
            'subtype' => 'carousel'
        ]);
    }
}
