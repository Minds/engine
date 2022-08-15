<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache;

use Minds\Core\Config\Config;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Metadata;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $db;

    /** @var Config */
    protected $config;

    public function let(
        Client $db,
        Config $config
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->beConstructedWith(
            $db,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_from_cassandra()
    {
        $url = 'https://www.minds.com/';
        $storedData = ['data' => $url];
        $this->db->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === 'SELECT * FROM metascraper_cache WHERE url_md5_hash = ?';
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([$storedData], ''));
        $this->get($url)->shouldBe($storedData);
    }

    public function it_should_upsert_to_cassandra(Metadata $data)
    {
        $url = 'https://www.minds.com/';
        $storedData = [1];

        $data->jsonSerialize()
            ->shouldBeCalled()
            ->willReturn(json_encode($storedData));

        $this->db->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn(new Rows([$storedData], ''));

        $this->upsert($url, $data)->shouldBe(true);
    }

    public function it_should_delete_from_cassandra()
    {
        $url = 'https://www.minds.com/';
        $storedData = [1];

        $this->db->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === 'DELETE FROM metascraper_cache WHERE url_md5_hash = ?';
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([$storedData], ''));

        $this->delete($url)->shouldBe(true);
    }
}
