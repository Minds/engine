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
    /** The url of the Expo GQL API. */
    public readonly string $gqlApiUrl;

    /** The base url of the Expo HTTP API. */
    public readonly string $httpApiBaseUrl;

    /** The ID of the expo account. */
    public readonly string $accountId;

    /** The name of the expo account. */
    public readonly string $accountName;

    /** The bearer token that provides access to the project. */
    public readonly string $bearerToken;

    /** The Expo ID for the apple team we are using. */
    public readonly string $appleTeamId;

    public function __construct(private Config $config)
    {
        $this->gqlApiUrl = $this->config->get('expo')['gql_api_url'] ?? 'https://api.expo.dev/graphql';
        $this->httpApiBaseUrl = $this->config->get('expo')['http_api_base_url'] ?? 'https://api.expo.dev/graphql';
        $this->accountId = $this->config->get('expo')['account_id'] ?? throw new ServerErrorException('No Expo account_id is configured.');
        $this->accountName = $this->config->get('expo')['account_name'] ?? throw new ServerErrorException('No Expo account_name is configured.');
        $this->bearerToken = $this->config->get('expo')['bearer_token'] ?? throw new ServerErrorException('No bearer_token provided for Expo client.');
        $this->appleTeamId = $this->config->get('expo')['apple_team_id'] ?? throw new ServerErrorException('No apple_team_id is configured.');
    }
}
