<?php
namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Wire\Paywall;
use Minds\Entities\Activity;
use Minds\Core\Di\Di;
use Minds\Helpers;

class PaywallDelegate
{
    /** @var Paywall\Manager */
    private $paywallManager;

    public function __construct($paywallManager = null)
    {
        $this->paywallManager = $paywallManager ?? Di::_()->get('Wire\Paywall\Manager');
    }

    /**
     * On adding the activity
     * @param Activity $activity
     * @return void
     */
    public function onAdd(Activity $activity): void
    {
        $this->paywallManager->validateEntity($activity);
    }
}
