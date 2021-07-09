<?php
namespace Spec\Minds\Core\Register;

use PhpSpec\ObjectBehavior;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Register\Controller;
use Minds\Core\Register\Manager;
use Minds\Entities\User;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Prophecy\Argument;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Register\Controller');
    }

    public function it_should_validate_username(
        ServerRequest $request
    ) {
        $username = 'bubbles';

        $request->getQueryParams()
                ->willReturn([
                'username' => $username,
            ]);

        $this->manager->validateUsername($username)
            ->willReturn(true);

        $response = $this->validate($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'valid' => true
        ]));
    }
}
