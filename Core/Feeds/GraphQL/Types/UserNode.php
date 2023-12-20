<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Helpers\StringLengthValidators\BriefDescriptionLengthValidator;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * Wraps the internal User class to work with GraphQL types
 */
#[Type]
class UserNode extends AbstractEntityNode
{
    public function __construct(
        protected User $user,
        protected ?User $loggedInUser = null
    ) {
        $this->entity = $user;
        $this->loggedInUser ??= Session::getLoggedinUser();
    }

    #[Field]
    public function getName(): string
    {
        return $this->user->getName();
    }

    #[Field]
    public function getUsername(): string
    {
        return $this->user->getUsername();
    }

    #[Field]
    public function getBriefDescription(): string
    {
        return (new BriefDescriptionLengthValidator())->validateMaxAndTrim($this->user->briefdescription);
    }

    #[Field(description: 'You are subscribed to this user')]
    public function getIsSubscribed(): bool
    {
        return $this->loggedInUser->isSubscribed($this->user->getGuid());
    }

    #[Field(description: 'The user is subscribed to you')]
    public function getIsSubscriber(): bool
    {
        return $this->loggedInUser->isSubscribed($this->user->getGuid());
    }

    #[Field(description: 'The user is a member of Minds+')]
    public function getIsPlus(): bool
    {
        return $this->user->isPlus();
    }

    #[Field(description: 'The user is a member of Minds Pro')]
    public function getIsPro(): bool
    {
        return $this->user->isPro();
    }

    #[Field(description: 'The user is a verified')]
    public function getIsVerified(): bool
    {
        return (bool) $this->user->verified;
    }

    #[Field(description: 'The user is a founder (contributed to crowdfunding)')]
    public function getIsFounder(): bool
    {
        return (bool) $this->user->founder;
    }

    #[Field(description: 'The users public ETH address')]
    public function getEthAddress(): ?string
    {
        return $this->user->getEthWallet() ?: null;
    }

    #[Field(description: 'The number of views the users has received. Includes views from their posts')]
    public function getImpressionsCount(): int
    {
        return $this->user->getImpressions();
    }

    #[Field(description: 'The number of subscribers the user has')]
    public function getSubscribersCount(): int
    {
        if ($this->getUsername() === 'minds') {
            return 0; // Do not expose Minds subscribers count
        }
        return $this->user->getSubscribersCount();
    }

    #[Field(description: 'The number of channels the user is subscribed to')]
    public function getSubscriptionsCount(): int
    {
        return $this->user->getSubscriptionsCount();
    }

    #[Field]
    public function getIconUrl(): string
    {
        return $this->user->getIconURL();
    }
}
