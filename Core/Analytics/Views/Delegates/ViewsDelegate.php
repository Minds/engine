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

        $viewEvent->cm_platform = $view->getPlatform();
        $viewEvent->cm_source = $view->getSource();
        $viewEvent->cm_timestamp = $view->getTimestamp();
        $viewEvent->cm_salt = $view->getSalt();
        $viewEvent->cm_medium = $view->getMedium();
        $viewEvent->cm_campaign = $view->getCampaign();
        $viewEvent->cm_page_token = $view->getPageToken();
        $viewEvent->cm_delta = $view->getDelta();
        $viewEvent->cm_position = $view->getPosition();
        $viewEvent->cm_served_by_guid = $view->getServedByGuid();

        $this->viewsTopic->send($viewEvent);
    }
}
