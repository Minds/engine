<?php
/**
 * TraefikDynamoDb
 * @author edgebal
 */

namespace Minds\Core\Pro\Domain\EdgeRouters;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Settings;

class TraefikDynamoDb implements EdgeRouterInterface
{
    /** @var Config */
    protected $config;

    /** @var DynamoDbClient */
    protected $dynamoDb;

    /**
     * TraefikDynamoDb constructor.
     * @param Config $config
     * @param DynamoDbClient $dynamoDb
     */
    public function __construct(
        $config = null,
        $dynamoDb = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->dynamoDb = $dynamoDb;
    }

    /**
     * @return EdgeRouterInterface
     */
    public function initialize(): EdgeRouterInterface
    {
        $awsConfig = $this->config->get('aws');

        $opts = [
            'region' => $awsConfig['region']
        ];

        if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
            $opts['credentials'] = [
                'key' => $awsConfig['key'],
                'secret' => $awsConfig['secret'],
            ];
        }

        if (isset($awsConfig['dynamoDbEndpoint'])) {
            $opts['endpoint'] = $awsConfig['dynamoDbEndpoint'];
        }

        $this->dynamoDb = new DynamoDbClient(array_merge([
            'version' => '2012-08-10',
        ], $opts));

        return $this;
    }

    /**
     * @param Settings $settings
     * @return bool
     */
    public function putEndpoint(Settings $settings): bool
    {
        $domain = $settings->getDomain();

        if (!$domain) {
            return false;
        }

        $userGuid = (string) $settings->getUserGuid();

        $marshaler = new Marshaler();
        $entry = $marshaler->marshalJson(json_encode([
            'id' => "minds-pro-frontend-{$userGuid}",
            'name' => "minds-pro-{$userGuid}",
            'frontend' => [
                'backend' => 'minds-pro',
                'routes' => [
                    'pro-domain' => [
                        'rule' => "Host: {$domain}"
                    ]
                ],
                'headers' => [
                    'SSLRedirect' => true,
                    'customrequestheaders' => [
                        'X-Minds-Pro' => '1',
                    ],
                ],
                'passHostHeader' => true,
            ],
        ]));

        try {
            $this->dynamoDb->putItem([
                'TableName' => $this->config->get('pro')['dynamodb_table_name'],
                'Item' => $entry,
            ]);

            return true;
        } catch (DynamoDbException $e) {
            error_log($e);
            return false;
        }
    }
}
