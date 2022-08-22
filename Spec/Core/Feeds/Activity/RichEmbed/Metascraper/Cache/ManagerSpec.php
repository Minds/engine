<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache;

use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Metadata;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache\Repository;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    public function let(
        Repository $repository,
    ) {
        $this->repository = $repository;
        $this->beConstructedWith($repository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_pre_exported_data_from_cache()
    {
        $key = 'cache-key';
        $data = [
            'var' => 1
        ];

        $this->repository->get($key)
            ->shouldBeCalled()
            ->willReturn(['data' => json_encode($data)]);

        $this->getExported($key)
            ->shouldBe($data);
    }

    public function it_should_return_null_if_no_pre_exported_data_in_cache()
    {
        $key = 'cache-key';

        $this->repository->get($key)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->getExported($key)
            ->shouldBe(null);
    }

    public function it_should_set_exported_metadata_in_cache(
        Metadata $metadata
    ) {
        $key = 'cache-key';

        $this->repository->upsert($key, Argument::any())
            ->shouldBeCalled();

        $this->set($key, $metadata);
    }

    public function it_should_delete_from_cache()
    {
        $key = 'cache-key';

        $this->repository->delete($key)
            ->shouldBeCalled();

        $this->delete($key);
    }
}
