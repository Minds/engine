<?php
declare(strict_types=1);

namespace Minds\Core\ActivityPub\Builders\Objects;

use DateTime;
use Exception;
use Minds\Core\ActivityPub\Manager as ActivityPubManager;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Entities\Activity;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;

class MindsActivityBuilder extends AbstractMindsEntityBuilder
{
    public function __construct(
        private readonly ActivityPubManager $activityPubManager,
    ) {
    }

    /**
     * @param Activity $activity
     * @return NoteType
     * @throws Exception
     */
    public function toActivityPubNote(Activity $activity): NoteType
    {
        $actorUri = $this->activityPubManager->getBaseUrl() . 'users/' . $activity->getOwnerGuid();
        $uri = $actorUri . '/entities/' . $activity->getGuid();
        if ($activity->getSource() === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            $uri = $this->activityPubManager->getUriFromEntity($activity);
        }


        $note = new NoteType();
        $note->id = $uri;
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

        if ($activity->hasAttachments() && $activity->getCustomType() === 'batch') {
            $note->attachment = $this->processAttachments($activity->getCustomData());
        }

        return $note;
    }

}
