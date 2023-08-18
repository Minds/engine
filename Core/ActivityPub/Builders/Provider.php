<?php
declare(strict_types=1);

namespace Minds\Core\ActivityPub\Builders;

use Minds\Core\ActivityPub\Builders\Objects\MindsActivityBuilder;
use Minds\Core\ActivityPub\Builders\Objects\MindsCommentBuilder;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(MindsActivityBuilder::class, function (Di $di): MindsActivityBuilder {
            return new MindsActivityBuilder(
                activityPubManager: $di->get(Manager::class),
            );
        });
        $this->di->bind(MindsCommentBuilder::class, function (Di $di): MindsCommentBuilder {
            return new MindsCommentBuilder(
                activityPubManager: $di->get(Manager::class),
            );
        });
    }
}
