<?php

namespace Spec\Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Data\ElasticSearch\Scroll;
use Minds\Core\SEO\Sitemaps\Resolvers\UsersResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Blockchain\Wallets\Balance;

class UsersResolverSpec extends ObjectBehavior
{
    protected $scroll;
    protected $balance;

    public function let(Scroll $scroll, Balance $balance)
    {
        $this->beConstructedWith($scroll, $balance);
        $this->scroll = $scroll;
        $this->balance = $balance;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UsersResolver::class);
    }

    // public function it_should_return_iterable_of_users()
    // {
    //     $this->scroll->request(Argument::any())
    //         ->shouldBeCalled()
    //         ->willReturn([
    //             [
    //                 '_source' => [
    //                     'username' => 'mark',
    //                     'guid' => 123
    //                 ]
    //                 ],
    //                 [
    //                     '_source' => [
    //                         'username' => 'marklowsubs',
    //                         'guid' => 456
    //                     ]
    //                 ]
    //         ]);
    //     // Nasty work around because User entities aren't injectable
    //     $cacher = \Minds\Core\Data\cache\factory::build();
    //     $cacher->set("123:friendsofcount", 10);
    //     $cacher->set("456:friendsofcount", 2);
    //     $this->getUrls()->shouldHaveCount(1);
    // }
}
