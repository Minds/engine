<?php

declare(strict_types=1);

namespace Minds\Core\Settings;

use Minds\Core\Di\Di;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Core\Settings\GraphQL\Types\Dismissal;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUser(User $user)
 */
class Manager
{
    use MagicAttributes;

    private ?User $user = null;

    public function __construct(
        private ?Repository $repository = null
    ) {
        $this->repository ??= Di::_()->get('Settings\Repository');
    }

    /**
     * @return UserSettings
     * @throws UserSettingsNotFoundException
     * @throws ServerErrorException
     */
    public function getUserSettings(bool $allowEmpty = false): UserSettings
    {
        try {
            return $this->repository
                ->getUserSettings($this->user->getGuid())
                ->withUser($this->user);
        } catch (UserSettingsNotFoundException $e) {
            if (!$allowEmpty) {
                throw $e;
            }
            return (new UserSettings())
                ->withUser($this->user);
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    public function storeUserSettings(array $data): bool
    {
        $settings = (new UserSettings())
            ->withUser($this->user)
            ->withData($data);

        return $this->repository->storeUserSettings($settings);
    }

    /**
     * Get a given users Dismissals.
     * @throws UserSettingsNotFoundException - if the user has no Dismissals.
     * @throws ServerErrorException - on error executing.
     * @return iterable - iterable of Dismissal objects.
     */
    public function getDismissals(): iterable
    {
        return $this->repository->getDismissals(
            (string) $this->user->getGuid()
        );
    }

    /**
     * Gets a Dismissal object for a user by dismissal key.
     * @param string $key - key to get Dismissal by.
     * @throws UserSettingsNotFoundException - if the user has no matching Dismissal.
     * @throws ServerErrorException - on error executing.
     * @return Dismissal - matching Dismissal object.
     */
    public function getDismissalByKey(string $key): Dismissal
    {
        return $this->repository->getDismissalByKey(
            (string) $this->user->getGuid(),
            $key
        );
    }

    /**
     * Upsert a Dismissal for a user into their JSON column for Dismissals.
     * @param string $key - key to upsert entry for.
     * @throws ServerErrorException - on error executing.
     * @return Dismissal - the new Dismissal.
     */
    public function upsertDismissal(string $key): Dismissal
    {
        $dismissals = $this->getUserSettings(allowEmpty: true)
                ->getRawDismissals(asArray: true);

        $existingArrayIndex = array_search(
            $key,
            array_column($dismissals, 'key'),
            true
        );

        $currentTimestamp = time();

        if (is_numeric($existingArrayIndex)) {
            $dismissals[$existingArrayIndex]['dismissal_timestamp'] = $currentTimestamp;
        } else {
            $dismissals[] = [
                'key' => $key,
                'dismissal_timestamp' => $currentTimestamp
            ];
        }

        $this->storeUserSettings([
            'dismissals' => json_encode($dismissals)
        ]);

        return new Dismissal(
            (string) $this->user->getGuid(),
            $key,
            $currentTimestamp
        );
    }
}
