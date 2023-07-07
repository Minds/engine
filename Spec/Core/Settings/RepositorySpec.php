<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Settings;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Core\Settings\GraphQL\Types\Dismissal;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Core\Settings\Repository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;

class RepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $mysqlHandler;
    private Collaborator $mysqlClientReader;
    private Collaborator $mysqlClientWriter;

    public function let(
        MySQLClient $mysqlHandler,
        PDO $mysqlClientReader,
        PDO $mysqlClientWriter
    ): void {
        $this->mysqlHandler = $mysqlHandler;

        $this->mysqlClientReader = $mysqlClientReader;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReader);

        $this->mysqlClientWriter = $mysqlClientWriter;
        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($this->mysqlClientWriter);

        $this->beConstructedWith($this->mysqlHandler);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(Repository::class);
    }

    /**
     * @param PDOStatement $statement
     * @return void
     * @throws ServerErrorException
     * @throws UserSettingsNotFoundException
     */
    public function it_should_successfully_get_user_settings(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $statement->fetch(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([
                'user_guid' => '123'
            ]);

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getUserSettings('123')
            ->shouldBeAnInstanceOf(UserSettings::class);
    }

    /**
     * @param PDOStatement $statement
     * @return void
     */
    public function it_should_throw_user_settings_not_found_exception_when_no_rows_match_in_db(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(0);

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->shouldThrow(UserSettingsNotFoundException::class)
            ->during('getUserSettings', ['123']);
    }

    /**
     * @param PDOStatement $statement
     * @param UserSettings $settings
     * @return void
     */
    public function it_should_successfully_store_settings(
        PDOStatement $statement,
        UserSettings $settings
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->mysqlClientWriter->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $settings->getUpdatedProperties()
            ->willReturn(['user_guid' => '123']);

        $this->storeUserSettings($settings)
            ->shouldBeEqualTo(true);
    }

    public function it_should_get_dismissals(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(3);

        $statement->fetch(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([
                'dismissals' => json_encode([
                    [
                        'key' => 'ANALYTICS_EXPLAINER',
                        'dismissal_timestamp' => 123
                    ],
                    [
                        'key' => 'BOOST_CONSOLE_EXPLAINER',
                        'dismissal_timestamp' => 234
                    ],
                    [
                        'key' => 'DISCOVERY_PLUS_EXPLAINER',
                        'dismissal_timestamp' => 345
                    ]
                ])
            ]);

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getDismissals('123')
            ->shouldBeAGeneratorWithValues([
                (new Dismissal(
                    '123',
                    'ANALYTICS_EXPLAINER',
                    123
                )),
                (new Dismissal(
                    '123',
                    'BOOST_CONSOLE_EXPLAINER',
                    234
                )),
                (new Dismissal(
                    '123',
                    'DISCOVERY_PLUS_EXPLAINER',
                    345
                )),
            ]);
    }

    public function it_should_get_a_dismissal_by_key(
        PDOStatement $statement
    ): void {
        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(3);

        $statement->fetch(Argument::type('integer'))
            ->shouldBeCalledOnce()
            ->willReturn([
                'dismissals' => json_encode([
                    [
                        'key' => 'ANALYTICS_EXPLAINER',
                        'dismissal_timestamp' => 123
                    ],
                    [
                        'key' => 'BOOST_CONSOLE_EXPLAINER',
                        'dismissal_timestamp' => 234
                    ],
                    [
                        'key' => 'DISCOVERY_PLUS_EXPLAINER',
                        'dismissal_timestamp' => 345
                    ]
                ])
            ]);

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getDismissalByKey('123', 'ANALYTICS_EXPLAINER')
            ->shouldBeLike(new Dismissal(
                '123',
                'ANALYTICS_EXPLAINER',
                123
            ));
    }
}
