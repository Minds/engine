<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings\Repositories;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Log\Logger;
use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\Settings\Exceptions\SettingsNotFoundException;

/**
 * Cassandra Repository that handles supermind_settings as JSON strings attached to a User entity.
 */
class CassandraRepository implements RepositoryInterface
{
    public function __construct(
        private ?Client $cql = null,
        private ?Logger $logger = null
    ) {
        $this->cql ??= Di::_()->get('Database\Cassandra\Cql');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Get settings for a given user.
     * @throws SettingsNotFoundException - when settings are not found.
     * @param User $user - given user.
     * @return Settings - user settings.
     */
    public function get(User $user): ?Settings
    {
        $cql = "SELECT * FROM entities WHERE key = ? AND column1 = 'supermind_settings'";
        $values = [ $user->getGuid() ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        try {
            $result = $this->cql->request($prepared);

            if (!$result->first()) {
                throw new SettingsNotFoundException();
            }

            $parsedResult = json_decode($result->first()['value']);

            return (new Settings(
                minOffchainTokens: $parsedResult->min_offchain_tokens,
                minCash: $parsedResult->min_cash
            ));
        } catch (SettingsNotFoundException) {
            throw new SettingsNotFoundException();
        } catch (\Exception $e) {
            $this->logger->error($e);
            throw new SettingsNotFoundException();
        }
    }

    /**
     * Update settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to update to.
     * @return bool true if update was successful.
     */
    public function update(User $user, Settings $settings): bool
    {
        return $this->upsert($user, $settings);
    }

    /**
     * Insert settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to insert for user.
     * @return bool true if insert was successful.
     */
    public function insert(User $user, Settings $settings): bool
    {
        return $this->upsert($user, $settings);
    }

    /**
     * Upsert settings for a given user.
     * @param User $user - given user.
     * @param Settings $settings - settings to upsert.
     * @return bool true if upsert was successful.
     */
    private function upsert(User $user, Settings $settings): bool
    {
        $cql = "INSERT INTO entities (key, column1, value) VALUES (?, 'supermind_settings', ?)";
        $values = [
            $user->getGuid(),
            json_encode($settings)
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        try {
            $result = $this->cql->request($prepared);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return [];
        }

        return (bool) $result;
    }
}
