<?php
declare(strict_types=1);

namespace Minds\Core\ActivityPub\Builders;

use Minds\Core\ActivityPub\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use MindsCommentBuilder;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(MindsCommentBuilder::class, function (Di $di): MindsCommentBuilder {
            return new MindsCommentBuilder(
                activityPubManager: $di->get(Manager::class),
            );
        });
    }
}
