<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed;

use Minds\Core\Config\Config;
use Minds\Core\Feeds\Activity\RichEmbed\Iframely;
use Minds\Core\Feeds\Activity\RichEmbed\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ManagerSpec extends ObjectBehavior
{
    protected $config;
    protected $iframely;

    public function let(Iframely $iframely, Config $config)
    {
        $this->beConstructedWith($iframely, $config);
        $this->config = $config;
        $this->iframely = $iframely;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_rich_embed(
        ResponseInterface $response,
        StreamInterface $stream,
    ) {
        $this->config->get('iframely')->shouldBeCalled()->willReturn([
            'key' => '123',
            'origin' => 'https://www.minds.com/',
        ]);

        $stream->getContents()->willReturn('{"meta":{"description":"An open source, community-owned social network dedicated to privacy, free speech, monetization and decentralization. Break free from big censorship, algorith...","title":"The Leading Alternative Social Network","author":"Minds","canonical":"https://www.minds.com/","site":"Minds"}}');

        $response->getBody()->shouldBeCalled()->willReturn($stream);

        $this->iframely->request('GET', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($response);

        $this->getRichEmbed('https://www.minds.com/')
            ->shouldHaveKeyWithValue('meta', [
                'description' => "An open source, community-owned social network dedicated to privacy, free speech, monetization and decentralization. Break free from big censorship, algorith...",
                'title' => "The Leading Alternative Social Network",
                'author' => "Minds",
                'canonical' => "https://www.minds.com/",
                'site' => "Minds"
            ]);
    }

    public function it_should_throw_exception_when_iframely_returns_non_200_code(
        ResponseInterface $response,
        StreamInterface $stream,
    ) {
        $this->config->get('iframely')->shouldBeCalled()->willReturn([
            'key' => '123',
            'origin' => 'https://www.minds.com/',
        ]);

        $stream->getContents()->willReturn('{"meta":{"status":417}}');

        $response->getBody()->shouldBeCalled()->willReturn($stream);

        $this->iframely->request('GET', Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($response);

        $this->shouldThrow(\Exception::class)->during('getRichEmbed', ['https://www.minds.com/']);
    }
}
