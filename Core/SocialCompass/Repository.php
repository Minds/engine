<?php

namespace Minds\Core\SocialCompass;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Di\Di;
use Minds\Core\SocialCompass\Entities\AnswerModel;

class Repository implements RepositoryInterface
{
    private ?Client $cql;

    public function __construct(
        ?Client $cql = null
    ) {
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
    }

    public function getAnswers(int $userGuid): array|null|false
    {
        $statement = "SELECT *
            FROM
                minds.social_compass_answers
            WHERE
                user_guid = ?";
        $values = [new Bigint($userGuid)];

        $query = $this->prepareQuery($statement, $values);

        $rows = $this->cql->request($query);
        if (!$rows) {
            return $rows;
        }

        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->prepareAnswer($row);
        }

        return $results;
    }

    public function getAnswerByQuestionId(int $userGuid, string $questionId): AnswerModel|null|false
    {
        $statement = "SELECT *
            FROM
                minds.social_compass_answers
            WHERE
                user_guid = ? AND question_id = ?";
        $values = [
            new Bigint($userGuid),
            $questionId
        ];

        $query = $this->prepareQuery($statement, $values);

        $rows = $this->cql->request($query);

        return $rows ? $this->prepareAnswer($rows->first()) : $rows;
    }

    private function prepareAnswer(?array $row): ?AnswerModel
    {
        if (!$row) {
            return null;
        }

        return new AnswerModel(
            $row['user_guid'],
            $row['question_id'],
            $row['current_value']
        );
    }

    private function prepareQuery(string $statement, array $values) : CustomQuery
    {
        $query = new CustomQuery();
        $query->query($statement, $values);

        return $query;
    }

    public function storeAnswers(int $userGuid, array $answers): bool
    {
        $dbUserGuid = new Bigint($userGuid);
        $queries = $this->createAnswersInsertQueries($dbUserGuid, $answers);

        $failedInserts = $this->processQueries($queries);

        return count($failedInserts) == 0;
    }

    private function createAnswersInsertQueries(Bigint $userGuid, array $answers) : array
    {
        $queries = [];
        foreach ($answers as $questionId => $answerValue) {
            $statement = "INSERT INTO
                        minds.social_compass_answers
                            (
                             user_guid,
                             question_id,
                             current_value
                            )
                        VALUES
                            (?, ?, ?)";
            $values = [$userGuid, $questionId, $answerValue];

            $this->addQueryIntoArray($queries, $statement, $values);
        }

        return $queries;
    }

    private function addQueryIntoArray(array &$array, string $statement, array $values) : void
    {
        array_push($array, $this->prepareQuery($statement, $values));
    }

    private function processQueries(array $queries) : array
    {
        $failedQueries = [];
        foreach ($queries as $query) {
            $result = $this->cql->request($query);
            if (!$result) {
                array_push($failedQueries, $query);
            }
        }

        return $failedQueries;
    }

    public function updateAnswers(int $userGuid, array $answers): bool
    {
        return $this->storeAnswers($userGuid, $answers);
    }
}
