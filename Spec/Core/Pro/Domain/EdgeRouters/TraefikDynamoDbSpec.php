<?php

namespace Spec\Minds\Core\Pro\Domain\EdgeRouters;

use Aws\DynamoDb\DynamoDbClient;
use Minds\Core\Config;
use Minds\Core\Pro\Domain\EdgeRouters\TraefikDynamoDb;
use Minds\Core\Pro\Settings;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TraefikDynamoDbSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var DynamoDbClient */
    protected $dynamoDb;

    public function let(
        Config $config,
        DynamoDbClient $dynamoDbClient
    ) {
        $this->config = $config;
        $this->dynamoDb = $dynamoDbClient;

        $this->beConstructedWith($config, $dynamoDbClient);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TraefikDynamoDb::class);
    }

    // NOTE: Cannot mock $this->initialize()

    public function it_should_put_endpoint(
        Settings $settings
    ) {
        $settings->getDomain()
            ->shouldBeCalled()
            ->willReturn('phpspec.test');

        $settings->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'dynamodb_table_name' => 'phpspec'
            ]);

        $this->dynamoDb->putItem(Argument::that(function ($args) {
            return $args['TableName'] === 'phpspec';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->putEndpoint($settings)
            ->shouldReturn(true);
    }
}
