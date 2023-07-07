<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Suggestions\DefaultTagMapping;

use Minds\Core\Data\MySQL\Client;
use Minds\Core\Suggestions\DefaultTagMapping\Repository;
use Minds\Core\Suggestions\Suggestion;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class RepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    protected Collaborator $mysqlClient;
    protected Collaborator $mysqlQueryBuilder;

    public function let(
        Client $mysqlClient,
        Connection $mysqlQueryBuilder,
        PDO $pdo
    ) {
        $mysqlClient->getConnection(Client::CONNECTION_REPLICA)->willReturn($pdo);

        $this->beConstructedWith($mysqlClient, $mysqlQueryBuilder);

        $this->mysqlClient = $mysqlClient;
        $this->mysqlQueryBuilder = $mysqlQueryBuilder;
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_list_when_tags_provided(
        SelectQuery $selectQuery,
        PDOStatement $statement
    ) {
        $entityType = 'group';
        $tags = ['minds'];
        $limit = 12;

        $this->mysqlQueryBuilder->select()
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->distinct()
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->columns(['entity_guid'])
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->from('minds_default_tag_mapping')
            ->shouldBeCalled()
            ->willReturn($selectQuery);
                
        $selectQuery->whereWithNamedParameters(
            'tag_name',
            Operator::IN,
            'tags',
            count($tags)
        )
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->where('entity_type', Operator::EQ, new RawExp(':type'))
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->limit($limit)
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->prepare()
            ->shouldBeCalled()
            ->willReturn($statement);

        $this->mysqlClient->bindValuesToPreparedStatement(
            Argument::that(function ($arg) {
                return $arg instanceof PDOStatement;
            }),
            Argument::that(function ($arg) use ($entityType, $tags) {
                return $arg === [
                    'type' => $entityType,
                    'tags' => $tags
                ];
            })
        )
            ->shouldBeCalled();

        $statement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $statement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                ['entity_guid' => '123', 'entity_type' => 'group'],
                ['entity_guid' => '234', 'entity_type' => 'group']
            ]);

        $this->getList(
            entityType: $entityType,
            tags: $tags,
            limit: $limit
        )->shouldBeAGeneratorWithValues([
            (new Suggestion())
                ->setEntityGuid('123')
                ->setEntityType('group'),
            (new Suggestion())
                ->setEntityGuid('234')
                ->setEntityType('group')
        ]);
    }

    public function it_should_get_list_when_NO_tags_provided(
        SelectQuery $selectQuery,
        PDOStatement $statement
    ) {
        $entityType = 'group';
        $tags = [];
        $limit = 12;

        $this->mysqlQueryBuilder->select()
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->distinct()
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->columns(['entity_guid'])
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->from('minds_default_tag_mapping')
            ->shouldBeCalled()
            ->willReturn($selectQuery);
                
        $selectQuery->whereWithNamedParameters(
            'tag_name',
            Operator::IN,
            'tags',
            count(['default'])
        )
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->where('entity_type', Operator::EQ, new RawExp(':type'))
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->limit($limit)
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->prepare()
            ->shouldBeCalled()
            ->willReturn($statement);

        $this->mysqlClient->bindValuesToPreparedStatement(
            Argument::that(function ($arg) {
                return $arg instanceof PDOStatement;
            }),
            Argument::that(function ($arg) use ($entityType, $tags) {
                return $arg === [
                    'type' => $entityType,
                    'tags' => ['default']
                ];
            })
        )
            ->shouldBeCalled();

        $statement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $statement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                ['entity_guid' => '123', 'entity_type' => 'group'],
                ['entity_guid' => '234', 'entity_type' => 'group']
            ]);

        $this->getList(
            entityType: $entityType,
            tags: $tags,
            limit: $limit
        )->shouldBeAGeneratorWithValues([
            (new Suggestion())
                ->setEntityGuid('123')
                ->setEntityType('group'),
            (new Suggestion())
                ->setEntityGuid('234')
                ->setEntityType('group')
        ]);
    }
}
