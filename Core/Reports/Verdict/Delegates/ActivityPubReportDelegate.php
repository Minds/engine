<?php
declare(strict_types=1);

namespace Minds\Core\Reports\Verdict\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Reports\Report;

class ActivityPubReportDelegate
{
    public function __construct(
        private readonly ActionEventsTopic $actionEventsTopic
    ) {
    }

    public function onReportUpheld(Report $report): void
    {
        $event = (new ActionEvent())
            ->setAction(ActionEvent::ACTION_UPHELD_REPORT)
            ->setUser(new SystemUser())
            ->setEntity($report->getEntity())
            ->setTimestamp(time());

        $this->actionEventsTopic->send($event);
    }
}
