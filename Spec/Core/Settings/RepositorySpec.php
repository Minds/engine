<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Settings;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Core\Settings\Repository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlHandler;
    private Collaborator $mysqlClientReader;
    private Collaborator $mysqlClientWriter;

    public function let(
        MySQLClient $mysqlHandler,
        PDO $pdo
    ): void {
        $this->mysqlHandler = $mysqlHandler;

        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($pdo);
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);

        $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($pdo);
        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);

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
        $expectedOutput = (new UserSettings())
            ->setUserGuid('123');

        $statement->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $statement->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $this->mysqlClientReader->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, Argument::type('array'))
            ->shouldBeCalledOnce();

        $this->getUserSettings('123')
            ->shouldBeEqualTo($expectedOutput);
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

        $this->shouldThrow(UserSettingsNotFoundException::class)
            ->during('getUserSettings');
    }

    /**
     * @param PDOStatement $statement
     * @param UserSettings $settings
     * @return void
     */
    public function it_should_successfully_store_settings_with_no_pre_existing_settings(
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

        $this->storeUserSettings($settings)
            ->shouldBeEqualTo(true);
    }
}
