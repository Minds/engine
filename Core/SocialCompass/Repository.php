<?php

namespace Minds\Core\SocialCompass;

use Cassandra\Bigint;
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
        private ?Urn $urn = null)
    {
        $this->cql = $this->cql ?? Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $this->scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->urn = $this->urn ?? new Urn;
    }

    public function getAnswers(int $userGuid, ?int $version = null) : array
    {
        $statement = "SELECT *
            FROM
                social_compass_answers
            WHERE
                user_guid = ?";
        $values = [new Bigint($userGuid)];

        if ($version) {
            $statement .= " AND version = ?";
            array_push($values, $version);
        }

        $query = $this->prepareQuery($statement, $values);

        return $this->cql->request($query);
    }

    private function prepareQuery(string $statement, array $values) : CustomQuery
    {
        $query = new CustomQuery();
        $query->query($statement, $values);

        return $query;
    }

    function storeAnswers(int $userGuid, array $answers): bool
    {
        $values = [];
        $dbUserGuid = new Bigint($userGuid);

        $statement = "INSERT INTO
                        social_compass_answers
                            (
                             user_guid,
                             question_id,
                             current_value
                            )
                        VALUES
                            ";

        foreach ($answers as $questionId => $answerValue) {
            $statement .= "(?, ?, ?),";
            array_push($values, $dbUserGuid, $questionId, $answerValue);
        }

        $statement = rtrim($statement, ",");

        $query = $this->prepareQuery($statement, $values);

        return $this->cql->request($query) == null;
    }

    function updateAnswers(int $userGuid, array $answers): bool
    {

    }
}
