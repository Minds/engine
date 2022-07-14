<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Metadata;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CacheSpec extends ObjectBehavior
{
    /** @var PsrWrapper */
    protected $cache;

    public function let(
        PsrWrapper $cache
    ) {
        $this->beConstructedWith(
            $cache
        );
        $this->cache = $cache;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Cache::class);
    }

    public function it_should_get_pre_exported_data_from_cache()
    {
        $key = 'cache-key';
        $data = [
            'var' => 1
        ];

        $this->cache->get($key)
            ->shouldBeCalled()
            ->willReturn(json_encode($data));

        $this->getExported($key)
            ->shouldBe($data);
    }

    public function it_should_return_null_if_no_pre_exported_data_in_cache()
    {
        $key = 'cache-key';

        $this->cache->get($key)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->getExported($key)
            ->shouldBe(null);
    }

    public function it_should_set_exported_metadata_in_cache(
        Metadata $metadata
    ) {
        $key = 'cache-key';

        $this->cache->set($key, Argument::any(), 86400)
            ->shouldBeCalled();

        $this->set($key, $metadata);
    }
}
