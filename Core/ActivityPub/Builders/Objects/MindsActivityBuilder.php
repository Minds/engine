<?php
declare(strict_types=1);

use Minds\Core\ActivityPub\Manager as ActivityPubManager;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Entities\Activity;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;

class MindsActivityBuilder
{
    public function __construct(
        private readonly ActivityPubManager $activityPubManager,
    ) {
    }

    /**
     * @param Comment $comment
     * @return NoteType
     * @throws Exception
     */
    public function toActivityPubNote(Activity $activity): NoteType
    {
        $actorUri = $this->activityPubManager->getBaseUrl() . 'users/' . $activity->getOwnerGuid();
        $note = new NoteType();
        $note->id = $activity->getSource() === FederatedEntitySourcesEnum::LOCAL ? $actorUri . '/entities/' . $activity->getGuid() : $activity->getCanonicalUrl();
        $note->content = $activity->getMessage();
        $note->attributedTo = $actorUri;
        $note->published = new Datetime(date('c', $activity->getTimeCreated()));
        $note->to = [
            'https://www.w3.org/ns/activitystreams#Public',
        ];
        $note->cc = [
            $actorUri . '/followers',
        ];
        $note->url = $activity->getUrl();

        if ($activity->isQuotedPost()) {
            $note->inReplyTo = $this->activityPubManager->getUriFromEntity($activity->getRemind());
        }
        return $note;
    }

    private function getReplyToUri(Comment $comment): string
    {
        if ($comment->getParentGuid()) {
            $parentUrn = $comment->getParentUrn();
        } else {
            $parentUrn = 'urn:entity:' . $comment->getEntityGuid();
        }

        /**
         * Get the uri of what we are replying to
         */
        return $this->activityPubManager->getUriFromUrn($parentUrn);
    }

}
