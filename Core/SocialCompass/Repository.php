<?php

namespace Minds\Core\SocialCompass;

use Cassandra\Bigint;
use Cassandra\Rows;
use Minds\Common\Urn;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Di\Di;

class Repository implements RepositoryInterface
{
    public function __construct(
        private ?Client $cql = null,
        private ?Scroll $scroll = null,
        private ?Urn $urn = null
    )
    {
        $this->cql = $this->cql ?? Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $this->scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->urn = $this->urn ?? new Urn();
    }

    public function getAnswers(int $userGuid, ?int $version = null) : Rows|null|false
    {
        $statement = "SELECT *
            FROM
                minds.social_compass_answers
            WHERE
                user_guid = ?";
        $values = [new Bigint($userGuid)];

        $query = $this->prepareQuery($statement, $values);

        return $this->cql->request($query);
    }

    public function getAnswerByQuestionId(int $userGuid, string $questionId) : Rows|null|false
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

        return $this->cql->request($query);
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
            $values = array($userGuid, $questionId, $answerValue);

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
        foreach ($queries as $query)
        {
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
