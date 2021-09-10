<?php

namespace Spec\Minds\Core\Helpdesk\Zendesk;

use Minds\Core\Config\Config;
use Minds\Core\Helpdesk\Zendesk\Controller;
use Minds\Core\Helpdesk\Zendesk\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    private $manager;

    /** @var Config */
    private $config;

    public function let(
        Manager $manager,
        Config $config
    ) {
        $this->manager = $manager;
        $this->config = $config;
    
        $this->beConstructedWith($manager, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_redirect(ServerRequest $request, User $user)
    {
        $request->getAttribute('_user')
            ->willReturn($user);
        
        $request->getQueryParams()->willReturn([]);

        $this->manager->getJwt($user)->shouldBeCalled()->willReturn('valid_base64');

        $this->config->get('zendesk')->shouldBeCalled()->willReturn([
            'url' => [
                'base' => 'https://minds.zendesk.com/',
                'jwt_route' => 'access/jwt'
            ]
        ]);

        $this->redirect($request);
    }

    public function it_should_return_url_when_requested(ServerRequest $request, User $user)
    {
        $request->getAttribute('_user')
            ->willReturn($user);
        
        $request->getQueryParams()->willReturn([
            'returnUrl' => 'true'
        ]);

        $this->manager->getJwt($user)->shouldBeCalled()->willReturn('valid_base64');

        $this->config->get('zendesk')->shouldBeCalled()->willReturn([
            'url' => [
                'base' => 'https://minds.zendesk.com/',
                'jwt_route' => 'access/jwt'
            ]
        ]);

        $response = $this->redirect($request);

        $response
            ->getBody()
            ->getContents()
            ->shouldBe(json_encode([
                'status' => 'success',
                'url' => 'https%3A%2F%2Fminds.zendesk.com%2Faccess%2Fjwt%3Fjwt%3Dvalid_base64'
            ]));
    }
}
