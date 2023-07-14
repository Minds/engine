<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Votes;
use Minds\Entities\Activity;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Wraps the internal Activity class to work with GraphQL types
 */
#[Type]
class ActivityNode extends AbstractEntityNode
{
    public function __construct(
        protected Activity $activity,
        protected ?User $loggedInUser = null,
        protected ?Votes\Manager $votesManager = null,
    ) {
        $this->entity = $activity;  // Pass 'entity' through to abstract lower layer
        $this->loggedInUser ??= Session::getLoggedinUser();
        $this->votesManager ??= Di::_()->get('Votes\Manager');
    }

    #[Field]
    public function getOwnerGuid(): string
    {
        return $this->activity->getOwnerGuid();
    }

    #[Field]
    public function getOwner(): UserNode
    {
        return new UserNode($this->activity->getOwnerEntity());
    }

    #[Field(description: 'Relevant for images/video posts')]
    public function getTitle(): ?string
    {
        return  $this->activity->getTitle();
    }

    #[Field]
    public function getMessage(): string
    {
        return (string) $this->activity->getMessage();
    }

    #[Field(description: 'Relevant for images/video posts. A blurhash to be used for preloading the image.')]
    public function getBlurhash(): ?string
    {
        return $this->activity->blurhash;
    }

    #[Field(description: 'The activity has comments enabled')]
    public function getIsCommentingEnabled(): bool
    {
        return (bool) $this->activity->getAllowComments();
    }

    #[Field]
    public function getImpressionsCount(): int
    {
        return $this->activity->getImpressions();
    }

    #[Field]
    public function getVotesUpCount(): int
    {
        return (int) $this->activity->{'thumbs:up:count'};
    }

    #[Field]
    public function getHasVotedUp(): bool
    {
        return $this->hasVoted(Votes\Enums\VoteDirectionEnum::UP);
    }

    #[Field]
    public function getVotesDownCount(): int
    {
        return (int) $this->activity->{'thumbs:down:count'};
    }

    #[Field]
    public function getHasVotedDown(): bool
    {
        return $this->hasVoted(Votes\Enums\VoteDirectionEnum::DOWN);
    }

    #[Field]
    public function getCommentsCount(): int
    {
        return 0; // TODO
    }

    /**
     * Helper function to determine if current logged in user has
     * voted on the post
     * @param int $direction - Votes\Enums\VoteDirectionEnum
     * @return bool
     */
    protected function hasVoted(int $direction): bool
    {
        if (!$this->loggedInUser) {
            return false;
        }
    
        $vote = (new Votes\Vote())
            ->setEntity($this->activity)
            ->setActor($this->loggedInUser)
            ->setDirection($direction === Votes\Enums\VoteDirectionEnum::UP ? 'up' : 'down');

        return $this->votesManager->has($vote);
    }
}
