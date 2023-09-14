<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Codeception\Attribute\When;
use Tests\Support\ApiTester;

#[Group(
    'supermind',
    'discovery'
)]
class SupermindSteps extends ApiTester
{
    #[Given('I login to ":action" Supermind requests')]
    public function givenILoginToInteractWithSupermindRequests(string $action)
    {
        if ($action === 'create') {
            $this->loginWithSupermindRequesterAccount();
        } else {
            $this->loginWithSupermindReceiverAccount();
        }
        $this->seeResponseCodeIs(200);
    }

    #[Given('I create a Supermind request with the following details :activityDetails')]
    public function givenICreateASupermindRequest(PyStringNode $activityDetails)
    {
        $activityDetails = json_decode($activityDetails->getRaw(), true);

        $this->loginWithSupermindRequesterAccount();
        $activityDetails['supermind_request'] = $this->populateActivitySupermindRequestDetails($activityDetails['supermind_request']);

        $this->createActivityWithDetails($activityDetails, true);
    }

    #[When('I accept the Supermind request for stored data ":dataToRetrieve" with the following reply :supermindReply')]
    public function whenIAcceptSupermindRequestStatusForStoredData(string $dataToRetrieve, PyStringNode $supermindReply)
    {
        //        $this->loginWithSupermindReceiverAccount();

        $supermindReply = json_decode($supermindReply->getRaw(), true);
        $supermindReply = $this->populateSupermindReplyDetails($supermindReply, $dataToRetrieve);
        $this->createActivityWithDetails($supermindReply);
    }

    #[When('I reject the Supermind request for stored data ":dataToRetrieve"')]
    public function whenIRejectSupermindRequestForStoredData(string $dataToRetrieve)
    {
        $this->rejectSupermindRequest($dataToRetrieve);
    }

    #[When('I call the single Supermind endpoint with last created Supermind guid')]
    public function whenICallTheSingleSupermindEndpointWithLastCreatedSupermindGuid()
    {
        $this->callSingleSupermindEndpoint();
    }
}
