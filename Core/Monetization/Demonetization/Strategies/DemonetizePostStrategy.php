<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Demonetization\Strategies;

use Minds\Core\Blogs\Blog;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizationStrategyInterface;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Entities\GuidLinkResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Exceptions\ServerErrorException;

/**
 * Strategy to demonetize a post, by setting the wire threshold to empty,
 * and paywall to false.
 */
class DemonetizePostStrategy implements DemonetizationStrategyInterface
{
    public function __construct(
        private ?SaveAction $saveAction = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?GuidLinkResolver $guidLinkResolver = null,
        private ?Logger $logger = null,
    ) {
        $this->saveAction ??= new SaveAction();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->guidLinkResolver ??= new GuidLinkResolver();
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Execute strategy to demonetize the given entity.
     * @param DemonetizableEntityInterface $entity - entity to demonetize. - must be implement PaywallEntityInterface.
     * @throws ServerErrorException if entity does not inherit PaywallEntityInterface.
     * @return boolean true if successful.
     */
    public function execute(DemonetizableEntityInterface $entity): bool
    {
        if (!$entity instanceof PaywallEntityInterface) {
            throw new ServerErrorException('Invalid entity passed to demonetize post strategy');
        }

        try {
            $this->removePaywallAttributes($entity);
            
            // remove paywall attributes for linked entity if one exists.
            if ($entity instanceof Blog || $entity instanceof Image || $entity instanceof Video) {
                if ($activityGuid = $this->guidLinkResolver->resolve($entity->getGuid())) {
                    $activity = $this->entitiesBuilder->single($activityGuid);

                    if ($activity instanceof Activity) {
                        $this->removePaywallAttributes($activity);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false;
        }
        
        return true;
    }

    /**
     * Removes paywall attributes from an entity.
     * @param PaywallEntityInterface $entity - entity to remove attributes from/
     * @return void
     */
    private function removePaywallAttributes(PaywallEntityInterface $entity): void
    {
        $entity->setWireThreshold([]);
        $entity->setPaywall(false);
        $this->saveAction->setEntity($entity)->save(true);
    }
}
