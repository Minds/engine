<?php
declare(strict_types=1);

namespace Minds\Core\Reports\Verdict\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(ActivityPubReportDelegate::class, function (Di $di): ActivityPubReportDelegate {
            return new ActivityPubReportDelegate(
                $di->get("EventStreams\Topics\ActionEventsTopic")
            );
        });
    }
}
