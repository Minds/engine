<?php
declare(strict_types=1);

namespace Minds\Core\Admin;

use Minds\Core\Email\Confirmation\Manager as EmailConfirmationManager;
use Minds\Core\Email\SpamFilter;
use Minds\Core\Email\Verify\Manager as EmailVerifyManager;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\TOTP\Manager as TOTPManager;
use Minds\Core\Security\TOTP\TOTPSecretQueryOpts;
use Minds\Entities\User;
use Minds\Exceptions\StopEventException;
use Minds\Exceptions\StringLengthException;
use Minds\Helpers\StringLengthValidators\UsernameLengthValidator;
use RegistrationException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Manager
{
    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Save $entitySaveHandler,
        private readonly TOTPManager $totpManager,
        private readonly EmailConfirmationManager $emailConfirmationManager,
        private readonly SpamFilter $spamFilter,
        private readonly EmailVerifyManager $emailVerifyManager,
        private readonly Logger $logger,
    ) {
    }

    /**
     * @param string $currentUsername
     * @param string|null $newUsername
     * @param string|null $newEmail
     * @param bool|null $resetMFA
     * @return bool
     * @throws GraphQLException
     * @throws RegistrationException
     * @throws StopEventException
     * @throws UnverifiedEmailException
     * @throws StringLengthException
     */
    public function updateAccount(
        string $currentUsername,
        ?string $newUsername,
        ?string $newEmail,
        ?bool $resetMFA,
    ): bool {
        $targetUser = $this->entitiesBuilder->getByUserByIndex($currentUsername);

        if (!$targetUser) {
            $targetUser = $this->entitiesBuilder->single($currentUsername);
        }

        if (!$targetUser) {
            $this->logger->error('Could not find user to update', [
                'currentUsername' => $currentUsername,
                'newUsername' => $newUsername,
                'newEmail' => $newEmail,
                'resetMFA' => $resetMFA,
            ]);

            throw new GraphQLException('Could not find user to update');
        }

        if ($newEmail) {
            $this->updateUserEmail($targetUser, $newEmail);
        }

        if ($resetMFA) {
            $this->resetUserMFA($targetUser);
        }

        if ($newUsername) {
            $this->updateUsername($targetUser, $newUsername);
        }

        return true;
    }

    /**
     * @param User $targetUser
     * @param string $newEmail
     * @return void
     * @throws GraphQLException
     * @throws RegistrationException
     */
    private function updateUserEmail(User $targetUser, string $newEmail): void
    {
        if (!$this->emailVerifyManager->verify($newEmail)) {
            throw new GraphQLException('Please verify the email address is correct');
        }

        if ($this->spamFilter->isSpam($newEmail)) {
            throw new GraphQLException("This email provider is blocked due to spam. Please use another address.");
        }

        if (!validate_email_address($newEmail)) {
            throw new GraphQLException("Invalid email");
        }

        $targetUser->setEmail($newEmail)->save();

        $this
            ->emailConfirmationManager
            ->setUser($targetUser)
            ->reset();
    }

    /**
     * @param User $targetUser
     * @return void
     */
    private function resetUserMFA(User $targetUser): void
    {
        $this->totpManager->delete(
            (new TOTPSecretQueryOpts())
                ->setUserGuid(
                    $targetUser->getGuid()
                )
        );

        $targetUser->telno = null;
        $targetUser->save();
    }

    /**
     * @param User $targetUser
     * @param string $newUsername
     * @return void
     * @throws GraphQLException
     * @throws StringLengthException
     */
    private function updateUsername(User $targetUser, string $newUsername): void
    {
        try {
            if (
                !(new UsernameLengthValidator())->validate($newUsername) ||
                !validate_username($newUsername) ||
                check_user_index_to_guid($newUsername)
            ) {
                throw new GraphQLException('Invalid username');
            }
        } catch (RegistrationException $e) {
            throw new GraphQLException($e->getMessage());
        }

        $targetUser->username = $newUsername;
        $targetUser->save();
    }
}
