<?php

namespace Spec\Minds\Core\Media\YouTubeImporter;

use Minds\Core\Media\YouTubeImporter\Controller;
use Minds\Core\Media\YouTubeImporter\Manager;
use Minds\Core\Media\YouTubeImporter\YTAuth;
use Minds\Core\Media\YouTubeImporter\YTSubscription;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Config\Config;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var YTAuth */
    protected $ytAuth;
    
    /** @var YTSubscription */
    protected $ytSubscription;
    
    /** @var Config */
    protected $config;

    public function let(Manager $manager, YTAuth $ytAuth, YTSubscription $ytSubscription, Config $config)
    {
        $this->manager = $manager;
        $this->ytAuth = $ytAuth;
        $this->ytSubscription = $ytSubscription;
        $this->config = $config;
        $this->beConstructedWith($manager, $ytAuth, $ytSubscription, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_get_an_oauth_token(ServerRequest $request)
    {
        $this->ytAuth->connect()
            ->willReturn('oauth-string');
        $response = $this->getToken($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
            'url' => 'oauth-string',
        ]));
    }

    public function it_should_disconnect_account(ServerRequest $request)
    {
        $request->getQueryParams()
            ->willReturn([
                'channelId' => 'testYTId',
            ]);
        $request->getAttribute('_user')
            ->willReturn(new User());

        $this->ytAuth->disconnect(Argument::type(User::class), 'testYTId')
            ->shouldBeCalled();

        $response = $this->disconnectAccount($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
        ]));
    }

    // Exit is called here which kills our tests
    // function it_should_receive_access_code(ServerRequest $request)
    // {
    //     $user = new User();
    //     $request->getQueryParams()
    //         ->willReturn([
    //             'code' => 'test-code',
    //         ]);
    //     $request->getAttribute('_user')
    //         ->willReturn($user);

    //     $this->ytAuth->fetchToken($user, 'test-code')
    //         ->shouldBeCalled();

    //     $response = $this->receiveAccessCode($request);
    //     $json = $response->getBody()->getContents();
    //     $json->shouldBe(json_encode([
    //         'status' => 'success',
    //     ]));
    // }
}
