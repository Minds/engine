<?php
namespace Minds\Core\Boost\LiquiditySpot\Delegates;

use Minds\Core\Analytics\Views;
use Minds\Core\Analytics\Views\View;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Di\Di;

class AnalyticsDelegate
{
    /** @var Views\Manager */
    protected $viewsManager;

    public function __construct(Views\Manager $viewsManager = null)
    {
        $this->viewsManager = $viewsManager ?? Di::_()->get('Analytics\Views\Manager');
    }

    /**
     * On get, we add a views metric
     * @param Boost $boost
     * @return void
     */
    public function onGet(Boost $boost): void
    {
        $entity = $boost->getEntity();
        $view = new View();
        $view->setEntityUrn($entity->getUrn())
            ->setOwnerGuid($entity->getOwnerGuid())
            ->setClientMeta([
                'campaign' => "liquidity-spot",
                'delta' =>  0,
                'medium' => "",
                'platform' =>  "web",
                'position' => 1,
                'source' => '',
                'timestamp' => time() * 1000,
            ]);

        $this->viewsManager->record($view);
    }
}
