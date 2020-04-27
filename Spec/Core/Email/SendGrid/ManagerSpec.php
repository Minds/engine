<?php

namespace Spec\Minds\Core\Email\SendGrid;

use Minds\Core\Email\SendGrid\Manager;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\Email\SendGrid\Lists\SendGridListInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var \SendGrid */
    protected $sendGrid;

    /** @var \SendGrid\Client */
    protected $sendGridClient;

    public function let(\SendGrid $sendGrid, \SendGrid\Client $sendGridClient)
    {
        $this->sendGridClient = $sendGridClient;
        $sendGrid->client = $this->sendGridClient;
        $this->beConstructedWith($sendGrid);
        $this->sendGrid = $sendGrid;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_sync_contact_lists(
        SendGridListInterface $list,
        \SendGrid\Client $marketing,
        \SendGrid\Client $fieldDefinitions,
        \SendGrid\Response $fieldDefinitionsResponse,
        \SendGrid\Client $contacts,
        \SendGrid\Response $contactsResponse
    ) {
        $this->sendGridClient->marketing()
            ->willReturn($marketing);

        $marketing->field_definitions()
            ->willReturn($fieldDefinitions);
        $fieldDefinitions->get()
            ->willReturn($fieldDefinitionsResponse);
        $fieldDefinitionsResponse->body()
            ->willReturn(json_encode([
                'custom_fields' => [
                    [
                        'id' => 'mock_1',
                        'name' => 'pro_expires',
                    ]
                ]
            ]));

        $marketing->contacts()
            ->willReturn($contacts);
        $contacts->put(Argument::any())
            ->willReturn($contactsResponse);
        $contactsResponse->statusCode()
            ->willReturn(202);

        $list->getContacts()
            ->shouldBeCalled()
            ->willReturn([
                (new SendGridContact)
                    ->setEmail('mark@minds.com')
                    ->setProExpires(time())
            ]);

        $this->syncContactLists([ $list ]);
    }
}
