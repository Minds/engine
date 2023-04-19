<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Suggestions\DefaultTagMapping;

use ArrayIterator;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Suggestions\DefaultTagMapping\Manager;
use Minds\Core\Suggestions\DefaultTagMapping\Repository;
use Minds\Core\Log\Logger;
use Minds\Core\Suggestions\Suggestion;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $logger;
    private Collaborator $cache;

    public function let(
        Repository $repository,
        Logger $logger,
        PsrWrapper $cache
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->cache = $cache;

        $this->beConstructedWith(
            $repository,
            $logger,
            $cache
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_suggestions_from_repository(): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

        $this->repository->getList(
            entityType: $entityType,
            tags: $tags
        )
            ->shouldBeCalled()
            ->willReturn(new ArrayIterator([
                $suggestion1,
                $suggestion2
            ]));
        
        $this->getSuggestions($entityType, $tags)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }

    public function it_should_fallback_get_suggestions_from_repository_when_no_results(): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

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
    
        $this->getSuggestions($entityType, $tags)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }

    public function it_should_fallback_get_suggestions_from_repository_on_exception(): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

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

        $this->getSuggestions($entityType, $tags)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }

    public function it_should_fallback_get_suggestions_from_cache(): void
    {
        $suggestion1 = new Suggestion();
        $suggestion2 = new Suggestion();

        $entityType = 'group';
        $tags = ['minds'];

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

        $this->getSuggestions($entityType, $tags)->shouldBeLike([
            $suggestion1,
            $suggestion2
        ]);
    }
}
