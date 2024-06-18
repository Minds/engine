<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\Views\Delegates;

use Minds\Core\Analytics\Views\View;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\Events\ViewEvent;
use Minds\Core\EventStreams\Topics\ViewsTopic;
use Minds\Core\Session;
use Minds\Entities\EntityInterface;

/**
 * Responsible to handle actions that should be triggered when an activity's view is performed
 */
class ViewsDelegate
{
    public function __construct(
        private ?ViewsTopic $viewsTopic = null,
    ) {
        $this->viewsTopic ??= Di::_()->get(ViewsTopic::class);
    }

    /**
     * Called when am activity's view is recorded. Sends an event to a pulsar topic
     * @param View $view
     * @param EntityInterface $entity
     * @return void
     */
    public function onRecordView(View $view, EntityInterface $entity): void
    {
        $user = Session::getLoggedinUser();

        if (!$user) {
            // TODO; https://gitlab.com/minds/engine/-/issues/2567
            // Bypass sending through to Pulsar for now
            return;
        }

        $viewEvent = (new ViewEvent())
            ->setUser($user)
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
        $viewEvent->external = $view->isExternal();

        $viewEvent->viewUUID = $view->getUuid();

        $this->viewsTopic->send($viewEvent);
    }
}
