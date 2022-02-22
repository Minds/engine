<?php

namespace Minds\Core\Governance;

use Minds\Core\Governance\Entities\ProposalModel;
use Minds\Exceptions\ServerErrorException;
use stdClass;
use Zend\Json\Json;

interface RepositoryInterface
{
    /**
     * Finds and returns the proposals of the Governance tab
     * provided by a specific user.
     * @return ProposalModel[] The list of proposals found in the database
     * @throws ServerErrorException
     */
    public function getProposals();

    /**
     * Returns the answer object for a specific questionId if it exists
     * @param string $proposalId
     * @return ProposalModel
     * @throws ServerErrorException
     */
    public function getProposalById(string $proposalId);

    /**
     * Returns the answer object for a specific questionId if it exists
     * @param string $proposalId
     * @return bool
     * @throws ServerErrorException
     */
    public function deleteProposal(string $proposalId): bool;

    /**
     * Returns the answer object for a specific questionId if it exists
     * @param stdClass $proposal
     * @return bool
     * @throws ServerErrorException
     */
    public function insertProposal(stdClass $proposal): bool;
}
