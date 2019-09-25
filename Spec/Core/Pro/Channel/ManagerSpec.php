<?php

namespace Spec\Minds\Core\Pro\Channel;

use Minds\Common\Repository\Response;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Feeds\Top\Manager as TopManager;
use Minds\Core\Pro\Channel\Manager;
use Minds\Core\Pro\Repository;
use Minds\Core\Pro\Settings;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var TopManager */
    protected $top;

    /** @var abstractCacher */
    protected $cache;

    public function let(
        Repository $repository,
        TopManager $top,
        abstractCacher $cache
    ) {
        $this->repository = $repository;
        $this->top = $top;
        $this->cache = $cache;

        $this->beConstructedWith($repository, $top, $cache);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_all_categories_content(
        User $user,
        Response $getListResponse,
        Response $topGetListResponse1,
        Response $topGetListResponse2Top,
        Response $topGetListResponse2Latest,
        Settings $settings
    ) {
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->repository->getList([
            'user_guid' => 1000
        ])

            ->shouldBeCalled()
            ->willReturn($getListResponse);

        $getListResponse->first()
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->cache->get(Argument::containingString('::1000'))
            ->shouldBeCalled()
            ->willReturn(null);

        $settings->getTagList()
            ->shouldBeCalled()
            ->willReturn([
                ['tag' => 'test1', 'label' => 'Test 1'],
                ['tag' => 'test2', 'label' => 'Test 2'],
            ]);

        $this->top->getList(Argument::that(function (array $opts) {
            return $opts['algorithm'] === 'top' && $opts['hashtags'] === ['test1'];
        }))
            ->shouldBeCalled()
            ->willReturn($topGetListResponse1);

        $topGetListResponse1->toArray()
            ->shouldBeCalled()
            ->willReturn([5000, 5001, 5002]);

        $this->top->getList(Argument::that(function (array $opts) {
            return $opts['algorithm'] === 'top' && $opts['hashtags'] === ['test2'];
        }))
            ->shouldBeCalled()
            ->willReturn($topGetListResponse2Top);

        $topGetListResponse2Top->toArray()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->top->getList(Argument::that(function (array $opts) {
            return $opts['algorithm'] === 'latest' && $opts['hashtags'] === ['test2'];
        }))
            ->shouldBeCalled()
            ->willReturn($topGetListResponse2Latest);

        $topGetListResponse2Latest->toArray()
            ->shouldBeCalled()
            ->willReturn([5100, 5101, 5102]);

        $output = [
            [
                'tag' => ['tag' => 'test1', 'label' => 'Test 1'],
                'content' => [5000, 5001, 5002],
            ],
            [
                'tag' => ['tag' => 'test2', 'label' => 'Test 2'],
                'content' => [5100, 5101, 5102],
            ],
        ];

        $this->cache->set(Argument::containingString('::1000'), $output, Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->getAllCategoriesContent()
            ->shouldReturn($output);
    }
}
