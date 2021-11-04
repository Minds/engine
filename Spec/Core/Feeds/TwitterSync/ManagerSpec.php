<?php

namespace Spec\Minds\Core\Feeds\TwitterSync;

use GuzzleHttp\Psr7\Response;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\RichEmbed;
use Minds\Core\Feeds\TwitterSync\Manager;
use Minds\Core\Feeds\TwitterSync\Client;
use Minds\Core\Feeds\TwitterSync\ConnectedAccount;
use Minds\Core\Feeds\TwitterSync\Delegates\ChannelLinksDelegate;
use Minds\Core\Feeds\TwitterSync\Repository;
use Minds\Core\Feeds\TwitterSync\TwitterUser;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class ManagerSpec extends ObjectBehavior
{
    protected $client;
    protected $repository;
    protected $entitiesBuilder;
    protected $saveAction;
    protected $richEmbedManager;

    public function let(
        Client $client,
        Repository $repository,
        Config $config,
        EntitiesBuilder $entitiesBuilder,
        Save $save,
        RichEmbed\Manager $richEmbedManager,
        ChannelLinksDelegate $channelLinksDelegate,
        Logger $logger
    ) {
        $this->beConstructedWith($client, $repository, $config, $entitiesBuilder, $save, $richEmbedManager, $channelLinksDelegate, $logger);
        $this->client = $client;
        $this->repository = $repository;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->saveAction = $save;
        $this->richEmbedManager = $richEmbedManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_copy_over_a_tweet(ConnectedAccount $connectedAccount, TwitterUser $twitterUser, User $user)
    {
        //
        $this->repository->getList()
            ->willReturn([
                $connectedAccount
            ]);

        //
        $connectedAccount->getTwitterUser()
            ->willReturn($twitterUser);
        $connectedAccount->getLastImportedTweetId()
            ->willReturn('456');
        $connectedAccount->getUserGuid()
            ->willReturn('123');
        //
        $twitterUser->getFollowersCount()
            ->willReturn(100000);
        $twitterUser->getUserId()
            ->willReturn(123);

        //

        $this->client->request('GET', Argument::any())
            ->willReturn(new JsonResponse([
                'data' => [
                    [
                        'id' => '789',
                        'text' => 'this is a tweet with a link https://t.co/8t6gxbh0j8',
                        'entities' => [
                            'urls' => [
                                [
                                    'url' => 'https://t.co/8t6gxbh0j8',
                                    'expanded_url' => 'https://www.minds.com/mark'
                                ]
                            ]
                        ]
                    ]
                ]
            ]));

        //

        $this->entitiesBuilder->single('123')
            ->willReturn($user);

        //

        $this->richEmbedManager->getRichEmbed('https://www.minds.com/mark')
            ->willReturn([
                'meta' => [
                    'description' => 'This is a rich embed',
                    'title' => 'Mark Harding (CTO)',
                ],
                'links' => [
                    'thumbnail' => [
                        [
                            'href' => 'https://www.minds.com/icon/mark'
                        ]
                    ]
                ]
            ]);

        //

        $this->saveAction->setEntity(Argument::that(function ($entity) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save()
            ->shouldBeCalled();

        //

        $connectedAccount->setLastImportedTweetId('789')
            ->shouldBeCalled();

        $this->repository->add($connectedAccount)
            ->shouldBeCalled();

        $this->syncTweets()->shouldHaveCount(1);
    }
}
