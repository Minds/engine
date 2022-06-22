<?php

namespace Minds\Core\SocialCompass;

use Cassandra\Bigint;
use Cassandra\Rows;
use Iterator;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Di\Di;
use Minds\Core\SocialCompass\Entities\AnswerModel;
use Minds\Exceptions\ServerErrorException;

class Repository implements RepositoryInterface
{
    private ?Client $cql;

    public function __construct(
        ?Client $cql = null
    ) {
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param int $userGuid
     * @return AnswerModel[]
     * @throws ServerErrorException
     */
    public function getAnswers(int $userGuid): array
    {
        $statement = "SELECT *
            FROM
                social_compass_answers
            WHERE
                user_guid = ?";
        $values = [new Bigint($userGuid)];

        $query = $this->prepareQuery($statement, $values);

        $rows = $this->cql->request($query);

        return $this->prepareAnswers($rows);
    }

    /**
     * Count answers to Social Compass questions provided by a specific user.
     * @param int $userGuid - guid of target user.
     * @return int - count of answers.
     * @throws ServerErrorException
     */
    public function countAnswers(int $userGuid): int
    {
        $statement = "SELECT Count(*)
            FROM
                social_compass_answers
            WHERE
                user_guid = ?";
        
        $values = [new Bigint($userGuid)];

        $query = $this->prepareQuery($statement, $values);

        $result = $this->cql->request($query);

        if (!$result || !isset($result[0]['count'])) {
            throw new ServerErrorException("Unable to count SocialCompass answers for user: $userGuid");
        }

        return $result[0]['count']->value() ?? 0;
    }

    /**
     * @param int $userGuid
     * @param string $questionId
     * @return AnswerModel
     * @throws ServerErrorException
     */
    public function getAnswerByQuestionId(int $userGuid, string $questionId): AnswerModel
    {
        $statement = "SELECT *
            FROM
                social_compass_answers
            WHERE
                user_guid = ? AND question_id = ?";
        $values = [
            new Bigint($userGuid),
            $questionId
        ];

        $query = $this->prepareQuery($statement, $values);

        $rows = $this->cql->request($query);

        if (!$rows) {
            throw new ServerErrorException("No answer has been found in the db for the provided user");
        }

        return $this->prepareAnswer($rows->first());
    }

    /**
     * @param Iterator|bool|null $rows
     * @return AnswerModel[]
     * @throws ServerErrorException
     */
    private function prepareAnswers(Iterator|bool|null $rows): array
    {
        if (!$rows) {
            throw new ServerErrorException("No answers have been found in the db for the provided user");
        }

        $results = [];
        foreach ($rows as $row) {
            $results[$row["question_id"]] = $this->prepareAnswer($row);
        }

        return $results;
    }

    /**
     * @param array{user_guid: Bigint, question_id: string, current_value: int} $row
     *                   [
     *                      "user_guid": Bigint,
     *                      "question_id": string,
     *                      "current_value": int
     *                   ]
     * @return AnswerModel
     */
    private function prepareAnswer(array $row): AnswerModel
    {
        return new AnswerModel(
            $row['user_guid'],
            $row['question_id'],
            $row['current_value']
        );
    }

    /**
     * @param string $statement
     * @param array $values The values for the parameters in the query statement
     * @return CustomQuery
     */
    private function prepareQuery(string $statement, array $values): CustomQuery
    {
        $query = new CustomQuery();
        $query->query($statement, $values);

        return $query;
    }

    /**
     * @param AnswerModel[] $answers
     * @return bool
     */
    public function storeAnswers(array $answers): bool
    {
        $queries = $this->createAnswersInsertQueries($answers);

        $failedInserts = $this->processQueries($queries);

        return count($failedInserts) == 0;
    }

    /**
     * @param AnswerModel[] $answers
     * @return CustomQuery[]
     */
    private function createAnswersInsertQueries(array $answers): array
    {
        $queries = [];
        foreach ($answers as $answer) {
            $statement = "INSERT INTO
                        social_compass_answers
                            (
                             user_guid,
                             question_id,
                             current_value
                            )
                        VALUES
                            (?, ?, ?)";
            $values = [$answer->getUserGuid(), $answer->getQuestionId(), $answer->getCurrentValue()];

            $this->addQueryIntoArray($queries, $statement, $values);
        }

        return $queries;
    }

    /**
     * @param CustomQuery[] $array
     * @param string $statement
     * @param array{Bigint, string, int} $values
     */
    private function addQueryIntoArray(array &$array, string $statement, array $values): void
    {
        array_push($array, $this->prepareQuery($statement, $values));
    }

    /**
     * @param CustomQuery[] $queries
     * @return CustomQuery[]
     */
    private function processQueries(array $queries): array
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

    /**
     * @param AnswerModel[] $answers
     * @return bool
     */
    public function updateAnswers(array $answers): bool
    {
        return $this->storeAnswers($answers);
    }
}
