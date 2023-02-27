<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\Views\Delegates;

use Minds\Core\Analytics\Views\View;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\Events\ViewEvent;
use Minds\Core\EventStreams\Topics\ViewsTopic;
use Minds\Core\Session;
use Minds\Entities\EntityInterface;

class ViewsDelegate
{
    public function __construct(
        private ?ViewsTopic $viewsTopic = null,
    ) {
        $this->viewsTopic ??= Di::_()->get(ViewsTopic::class);
    }

    public function onRecordView(View $view, EntityInterface $entity): void
    {
        $viewEvent = (new ViewEvent())
            ->setUser(Session::getLoggedinUser())
            ->setEntity($entity)
            ->setTimestamp($view->getTimestamp());

        $viewEvent->cmPlatform = $view->getPlatform();
        $viewEvent->cmSource = $view->getSource();
        $viewEvent->cmSalt = $view->getSalt();
        $viewEvent->cmMedium = $view->getMedium();
        $viewEvent->cmCampaign = $view->getCampaign();
        $viewEvent->cmPageToken = $view->getPageToken();
        $viewEvent->cmDelta = $view->getDelta();
        $viewEvent->cmPosition = $view->getPosition();
        $viewEvent->cmServedByGuid = $view->getServedByGuid();

        $viewEvent->viewUUID = $view->getUuid();

        $this->viewsTopic->send($viewEvent);
    }
}
