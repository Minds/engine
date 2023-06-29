<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Suggestions\DefaultTagMapping;

use ArrayIterator;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Core\Suggestions\DefaultTagMapping\Manager;
use Minds\Core\Suggestions\DefaultTagMapping\Repository;
use Minds\Core\Log\Logger;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $logger;
    private Collaborator $cache;
    private Collaborator $hashtagManager;

    public function let(
        Repository $repository,
        Logger $logger,
        PsrWrapper $cache,
        UserHashtagsManager $hashtagManager
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->hashtagManager = $hashtagManager;

        $this->beConstructedWith(
            $repository,
            $hashtagManager,
            $logger,
            $cache
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_suggestions_from_repository(User $user): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

        $this->hashtagManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->hashtagManager);
        
        $this->hashtagManager->get([
            'trending' => false,
            'defaults' => false
        ])
            ->shouldBeCalled()
            ->willReturn(array_map(function ($tagName) {
                return [ 'value' => $tagName ];
            }, $tags));

        $this->repository->getList(
            entityType: $entityType,
            tags: $tags
        )
            ->shouldBeCalled()
            ->willReturn(new ArrayIterator([
                $suggestion1,
                $suggestion2
            ]));
        
        $this->getSuggestions($user, $entityType)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }

    public function it_should_fallback_get_suggestions_from_repository_when_no_results(User $user): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

        $this->hashtagManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->hashtagManager);
    
        $this->hashtagManager->get([
            'trending' => false,
            'defaults' => false
        ])
            ->shouldBeCalled()
            ->willReturn(array_map(function ($tagName) {
                return [ 'value' => $tagName ];
            }, $tags));

        $this->repository->getList(
            entityType: $entityType,
            tags: $tags
        )
            ->shouldBeCalled()
            ->willReturn(new ArrayIterator([]));

        $this->cache->get('fallback_default_tag_suggestions:group')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->repository->getList(
            entityType: $entityType,
        )
            ->shouldBeCalled()
            ->willReturn(new ArrayIterator([
                $suggestion1,
                $suggestion2
            ]));

        $this->cache->set('fallback_default_tag_suggestions:group', Argument::type('string'), 86400)
            ->shouldBeCalled();
    
        $this->getSuggestions($user, $entityType)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }

    public function it_should_fallback_get_suggestions_from_repository_on_exception(User $user): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

        $this->hashtagManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->hashtagManager);
    
        $this->hashtagManager->get([
            'trending' => false,
            'defaults' => false
        ])
            ->shouldBeCalled()
            ->willReturn(array_map(function ($tagName) {
                return [ 'value' => $tagName ];
            }, $tags));

        $this->repository->getList(
            entityType: $entityType,
            tags: $tags
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('error occurred'));

        $this->logger->error(new \Exception('error occurred'))
            ->shouldBeCalled();

        $this->cache->get('fallback_default_tag_suggestions:group')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->repository->getList(
            entityType: $entityType,
        )
            ->shouldBeCalled()
            ->willReturn(new ArrayIterator([
                $suggestion1,
                $suggestion2
            ]));

        $this->cache->set('fallback_default_tag_suggestions:group', Argument::type('string'), 86400)
            ->shouldBeCalled();

        $this->getSuggestions($user, $entityType)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }

    public function it_should_fallback_get_suggestions_from_cache(User $user): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

        $this->hashtagManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->hashtagManager);
        
        $this->hashtagManager->get([
            'trending' => false,
            'defaults' => false
        ])
            ->shouldBeCalled()
            ->willReturn(array_map(function ($tagName) {
                return [ 'value' => $tagName ];
            }, $tags));

        $this->repository->getList(
            entityType: $entityType,
            tags: $tags
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('error occurred'));

        $this->logger->error(new \Exception('error occurred'))
            ->shouldBeCalled();

        $this->cache->get('fallback_default_tag_suggestions:group')
            ->shouldBeCalled()
            ->willReturn(serialize([
                $suggestion1,
                $suggestion2
            ]));

        $this->repository->getList(
            entityType: $entityType,
        )
            ->shouldNotBeCalled();

        $this->getSuggestions($user, $entityType)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }
}
