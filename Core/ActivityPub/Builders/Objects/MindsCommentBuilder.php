<?php
declare(strict_types=1);

namespace Minds\Core\ActivityPub\Builders\Objects;

use Minds\Core\ActivityPub\Manager as ActivityPubManager;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;

class MindsCommentBuilder
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
    public function toActivityPubNote(Comment $comment): NoteType
    {
        $actorUri = $this->activityPubManager->getBaseUrl() . 'users/' . $comment->getOwnerGuid();
        $note = new NoteType();
        $note->id = $comment->getSource() === FederatedEntitySourcesEnum::LOCAL ? $actorUri . '/entities/' . $comment->getUrn() : $comment->getCanonicalUrl();
        $note->content = $comment->getBody();
        $note->attributedTo = $actorUri;
        $note->published = new Datetime(date('c', $comment->getTimeCreated()));
        $note->inReplyTo = $this->getReplyToUri($comment);
        $note->to = [
            'https://www.w3.org/ns/activitystreams#Public',
        ];
        $note->cc = [
            $actorUri . '/followers',
        ];
        // TODO: Ask Mark where he added the getUrl for comments
        // $note->url = $comment->getUrl();
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
