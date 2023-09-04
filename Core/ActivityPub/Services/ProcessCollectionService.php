<?php
namespace Minds\Core\ActivityPub\Services;

use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Factories\ActivityFactory;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Exceptions\ServerErrorException;

class ProcessCollectionService
{
    protected array $json;
    protected AbstractActorType $actor;

    public function __construct(
        protected ProcessActivityService $processActivityService,
        protected ActivityFactory $activityFactory,
    ) {
        
    }

    public function withJson(array $json): ProcessCollectionService
    {
        $instance = clone $this;
        $instance->json = $json;
        return $instance;
    }

    public function withActor(AbstractActorType $actor): ProcessCollectionService
    {
        $instance = clone $this;
        $instance->actor = $actor;
        return $instance;
    }

    public function process(): void
    {
        if (!isset($this->json)) {
            throw new ServerErrorException('json must be provided');
        }
    
        if (!JsonLdHelper::isSupportedContext($this->json)) {
            return;
        }

        /**
         * Minds currently does not support Activity that is created by someone else
         */
        if ($this->isActorDifferent()) {
            return;
        }

        if ($this->isActorBanned()) {
            return;
        }

        if ($this->isActorLocal()) {
            return;
        }

        switch ($this->json['type']) {
            case 'Collection':
            case 'CollectionPage':
                $this->processItems($this->json['items']);
                break;
            case 'OrderedCollection':
            case 'OrderedCollectionPage':
                $this->processItems($this->json['orderedItems']);
                break;
            default:
                $this->processItems([$this->json]);
        }
    }

    private function processItems(array $items): void
    {
        $items = array_reverse($items);
        foreach ($items as $item) {
            $this->processItem($item);
        }
    }

    private function processItem(array $item): void
    {
        $apActivity = $this->activityFactory->fromJson($item, $this->actor);

        $this->processActivityService
            ->withActivity($apActivity)
            ->process();
    }

    private function isActorDifferent(): bool
    {
        return isset($this->json['actor']) && JsonLdHelper::getValueOrId($this->json['actor']) !== $this->actor->getId();
    }

    private function isActorBanned(): bool
    {
        return false;
    }

    private function isActorLocal(): bool
    {
        return false;
    }

}
