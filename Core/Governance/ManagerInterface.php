<?php

namespace Minds\Core\Governance;

use stdClass;


/**
 * The interface defining the methods to implement for the Governance module manager
 */
interface ManagerInterface
{
    /**
     * Retrieves the Governance tab Proposal set
     * @return array
     *         [
     *             "proposals": ProposalModel[]
     *             "proposalsProvided": bool
     *         ]
     */
    public function retrieveProposals(): array;

        /**
     * Retrieves the Governance tab questions set
     * @return array
     *         [
     *             "questions": ProposalModel[]
     *             "proposalsProvided": bool
     *         ]
     */
    public function retrieveProposal(string $proposalId): array;

    /**
     * Delete proposal by Id
     * @param string $id
     * @return $bool
     */
    public function delete(string $id): bool;

    /**
     * Insert proposal 
     * @param stdClass $proposal
     * @return $bool
     */
    public function insert(stdClass $proposal): bool;

}
