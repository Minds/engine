<?php

namespace Minds\Core\Governance;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use stdClass;

class Manager implements ManagerInterface
{

    public function __construct(
        private ?RepositoryInterface $repository = null,
        private ?User $targetUser = null
    ) {
        $this->repository = $this->repository ?? new Repository();

    }

    /**
     * @return array
     *         [
     *             "questions": ProposalModel[]
     *             "answersProvided": bool
     *         ]
     */
    public function retrieveProposals(): array
    {
        return $this->prepareGovernanceProposals();
    }


    /**
     * @return array
     *         [
     *             "questions": ProposalModel[]
     *             "answersProvided": bool
     *         ]
     */
    public function retrieveProposal(string $proposalId): array
    {
        return $this->prepareGovernanceProposal($proposalId);
    }

    /**
     * @param
     * @return array
     *         [
     *             "proposals": ProposalModel[]
     *             "proposalsProvided": bool
     *         ]
     */
    private function prepareGovernanceProposal(string $proposalId)
    {
        $results = [
            "proposals" => [],
            "proposalsProvided" => false
        ];

        $proposal = $this->repository->getProposalById($proposalId);

        array_push($results["proposals"], $proposal);

        $results["proposalsProvided"] = $proposal && count($results["proposals"]) > 0;

        return $results;
    }

    /**
     * @param
     * @return array
     *         [
     *             "proposals": ProposalModel[]
     *             "proposalsProvided": bool
     *         ]
     */
    private function prepareGovernanceProposals()
    {
        $results = [
            "proposals" => [],
            "proposalsProvided" => false
        ];

        $proposals = $this->repository->getProposals();

        array_push($results["proposals"], $proposals);

        $results["proposalsProvided"] = $proposals && count($results["proposals"]) > 0;

        return $results;
    }


    /**
     * Delete proposal
     * @param id $stdClass
     * @return bool
     */
    public function delete(string $id): bool
    {
        $success = $this->repository->deleteProposal($id);

        return $success;
    }

    /**
     * Delete activity
     * @param proposal $stdClass
     * @return bool
     */
    public function insert(stdClass $proposal): bool
    {
        $success = $this->repository->insertProposal($proposal);

        return $success;
    }
}
