<?php

namespace Minds\Core\Governance;


use Iterator;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Di\Di;
use Minds\Core\Governance\Entities\ProposalModel;
use Minds\Exceptions\ServerErrorException;
use stdClass;
use Zend\Diactoros\Response\JsonResponse;


class Repository implements RepositoryInterface
{
    private ?Client $cql;

    public function __construct(
        ?Client $cql = null
    ) {
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @return ProposalModel[]
     * @throws ServerErrorException
     */
    public function getProposals()
    {
        $statement = "SELECT * FROM governance";

        $values = [];

        $query = $this->prepareQuery($statement, $values);

        $rows = $this->cql->request($query);

        return $this->prepareAnswers($rows);
    }

    /**
     * @param string $proposalId
     * @return ProposalModel
     * @throws ServerErrorException
     */
    public function getProposalById(string $proposalId)
    {
        $statement = "SELECT *
            FROM
                governance
            WHERE
                proposal_id = ?";
        $values = [
            $proposalId
        ];

        $query = $this->prepareQuery($statement, $values);

        $rows = $this->cql->request($query);

        if (!$rows) {
            return new ServerErrorException('No row');
        }

        return $this->prepareAnswer($rows->first());
    }

    /**
     * @param Iterator|bool|null $rows
     * @return ProposalModel[]
     * @throws ServerErrorException
     */
    private function prepareAnswers(Iterator|null $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->prepareAnswer($row);
        }

        return $results;
    }

    /**
     * @param array{proposal_id: string, title: string, body: string,
     *              choices: Collection, type: string, category: string,
     *              author: string, time_created: Timestamp, time_end: Timestamp,
     *              snapshot_id: string, state: string, metadata: string} $row
     *                   [
     *                      "proposal_id": string,
     *                      "title": string,
     *                      "body": string,
     *                      "choices" : Collection,
     *                      "type": string,
     *                      "category": string,
     *                      "author": string,
     *                      "time_created": Timestamp,
     *                      "time_end": Timestamp,
     *                      "snapshot_id": string,
     *                      "state": string,
     *                      "metadata": string
     *                   ]
     * @return ProposalModel
     */
    private function prepareAnswer(array|null $row)
    {
        if (!$row || $row === null || $row['proposal_id'] === null) {
            return [];
        }

        $proposalItem = new ProposalModel();
        $proposalItem
            ->setProposalId($row['proposal_id'])
            ->setTitle($row['title'])
            ->setBody($row['body'])
            ->setChoices($row['choices'])
            ->setType($row['type'])
            ->setCategory($row['category'])
            ->setAuthor($row['author'])
            ->setTimeCreated($row['time_created'])
            ->setTimeEnd($row['time_end'])
            ->setSnapshotId($row['snapshot_id'])
            ->setState($row['state'])
            ->setMetadata($row['metadata']);

        return $proposalItem->export();
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
     * @param stdClass $proposal
     * @return bool
     */
    public function insertProposal(stdClass $proposal): bool
    {
        $statement = "INSERT INTO
                        governance
                            (
                            proposal_id,
                            title,
                            body,
                            choices,
                            type,
                            category,
                            author,
                            time_created,
                            time_end,
                            snapshot_id,
                            state,
                            metadata
                            )
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";


        $value = json_decode(json_encode($proposal), true);

        // $values = [
        //     $value['id'], $value['title'], $value['body'],
        //     $value['choices'], $value['type'], $value['category'], $value['author'],
        //     $value['start'], $value['end'],
        //     $value['snapshot'], $value['state'], $value['space']
        // ];

        $values = [
            'AtbyyzNXotHAS1Aojk6f9WUFQ9L95GgzVktDmVYcVfpjs1J', '¿Are pending proposals working?',
            '## Description\nTest', ['Maybe', 'Invalid options'], 'grant', 'community','0x6685dd9cb58bA8d27f5e2E9eB54A0Fe301c8F78C', 
            1236405066, 1636406276, '13578008', 'pending', '{"space":{ "id":"weenus", "name":"WEENUS"}}' 
        ];


        
        $query = $this->prepareQuery($statement, $values);

        // var_dump( $query );

        try {
            $this->cql->request($query);
            print_r('exito');
        } catch (\Exception $e) {
            print_r('error');
            var_dump($e);
            error_log($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param bool $proposalId
     * @return bool
     */
    public function deleteProposal($proposalId): bool
    {

        $proposal = $this->getProposalById($proposalId);
        if (!$proposal) {
            return false;
        }

        $cql = "DELETE FROM governance where proposal_id = ?";
        $values = [$proposalId];

        $query = $this->prepareQuery($cql, $values);

        try {
            $this->cql->request($query);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }

        return true;
    }
}
