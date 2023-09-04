<?php
/**
 * This subscription will sync comments to a relational DB and Elasticsearch/OpenSearch
 * You can test by running `php cli.php EventStreams --subscription=Core\\Comments\\CommentOpsEventStreamsSubscription`
 */

namespace Minds\Core\Comments;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;

use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;

class CommentOpsEventStreamsSubscription implements SubscriptionInterface
{
    protected Manager $manager;
    private SearchRepository $searchRepository;
    private RelationalRepository $repository;

    public function __construct(
        Manager $manager = null,
        RelationalRepository $repository = null,
        SearchRepository $searchRepository = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Comments\Manager');
        $this->searchRepository = $searchRepository ?? new SearchRepository();
        $this->repository = $repository ?? Di::_()->get(RelationalRepository::class);
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'comment-ops';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new EntitiesOpsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return EntitiesOpsTopic::TOPIC_NAME;
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (
            !$event instanceof EntitiesOpsEvent || // If not an an entity op event
            !str_contains($event->getEntityUrn(), "urn:comment") // Or not a comment
        ) {
            return true; // Ack
        }

        // If delete op, then remove from MySQL and ES
        if ($event->getOp() == EntitiesOpsEvent::OP_DELETE) {
            $urn = explode(':', $event->getEntityUrn()); // Split by ':'
            $guid = $urn[sizeof($urn) - 1]; // GUID is last element

            return $this->repository->delete($guid)
                && $this->searchRepository->delete($guid);
        }

        /** @var Comment **/
        $comment = $this->manager->getByUrn($event->getEntityUrn(), true);

        // If comment not found
        if (!$comment) {
            return true; // Ack
        }

        // Set date
        $timeCreated = date('c', $comment->getTimeCreated());
        $timeUpdated = date('c');

        // Set Parent GUID
        $depth = 0;
        $parentGuid = null;
        if ($comment->getParentGuidL2() > 0) {
            $depth = 2;
            $parentGuid = $comment->getParentGuidL2();
        } elseif ($comment->getParentGuidL1() > 0) {
            $depth = 1;
            $parentGuid = $comment->getParentGuidL1();
        }

        return $this->repository
            ->add($comment, $timeCreated, $timeUpdated, $parentGuid, $depth) // Add comment to relational database
            && $this->searchRepository
                ->add($comment, $timeCreated, $timeUpdated, $parentGuid, $depth); // Add comment to Elasticsearch
    }
}
