<?php
declare(strict_types=1);

namespace Minds\Core\Expo;

use Minds\Core\Config\Config;
use Minds\Exceptions\ServerErrorException;

/**
 * Config for Expo integrations.
 */
class ExpoConfig
{
    /** The url of the API. */
    public readonly string $apiUrl;

    /** The ID of the expo account. */
    public readonly string $accountId;

    /** The name of the expo account. */
    public readonly string $accountName;

    /** The id of the project we interact with. */
    public readonly string $projectId;

    /** The bearer token that provides access to the project. */
    public readonly string $bearerToken;

    /** The Expo ID for the apple team we are using. */
    public readonly string $appleTeamId;

    public function __construct(private Config $config)
    {
        $this->apiUrl = $this->config->get('expo')['api_url'] ?? 'https://api.expo.dev/graphql';
        $this->accountId = $this->config->get('expo')['account_id'] ?? throw new ServerErrorException('No Expo account_id is configured.');
        $this->accountName = $this->config->get('expo')['account_name'] ?? throw new ServerErrorException('No Expo account_name is configured.');
        $this->projectId = $this->config->get('expo')['project_id'] ?? throw new ServerErrorException('No Expo project_id is configured.');
        $this->bearerToken = $this->config->get('expo')['bearer_token'] ?? throw new ServerErrorException('No bearer_token provided for Expo client.');
        $this->appleTeamId = $this->config->get('expo')['apple_team_id'] ?? throw new ServerErrorException('No apple_team_id is configured.');
    }
}
