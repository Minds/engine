<?php

namespace Spec\Minds\Helpers;

use GuzzleHttp\Client as Guzzle_Client;
use GuzzleHttp\Psr7\Response;
use Minds\Core\Blogs\Blog;
use Minds\Helpers\NewsfeedActivityPubClient;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * Class NewsfeedActivityPubClientSpec
 * @package Spec\Minds\Helpers
 * @mixin NewsfeedActivityPubClient
 */
class NewsfeedActivityPubClientSpec extends ObjectBehavior
{
    protected function buildTestBlog(): Blog
    {
        $blog = new Blog();
        $blog->setTitle('Test Blog');
        $blog->setBody('Test body.');

        return $blog;
    }

    protected function buildGuzzleMock(): Guzzle_Client
    {
        $guzzleMock = new class extends Guzzle_Client {
            public function post($uri, $params): Response
            {
                return new class extends Response {
                    public function getStatusCode()
                    {
                        return 200;
                    }
                };
            }
        };

        return $guzzleMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Helpers\NewsfeedActivityPubClient');
    }

    public function it_should_not_post_blog_without_a_pub_server()
    {
        $this->shouldThrow(new \LogicException('The PubSub URI has not been specified.'))
            ->duringPostArticle($this->buildTestBlog(), null);
    }

    public function it_should_not_post_blog_without_an_actor()
    {
        $this->setActivityPubServer('https://test');
        $this->shouldThrow(new \LogicException('The PubSub actor has not been specified.'))
            ->duringPostArticle($this->buildTestBlog(), null);
    }

    public function it_should_post_blog_to_pub_server()
    {
        $this->beConstructedWith($this->buildGuzzleMock());

        $this->setActivityPubServer('http://localhost');
        $this->setActor('testuser', 'https://minds.com/testuser');
        $this->postArticle($this->buildTestBlog(), null)
            ->shouldBe(200);
    }

    public function it_should_not_like_an_entity_without_a_pub_server()
    {
        $this->shouldThrow(new \LogicException('The PubSub URI has not been specified.'))
            ->duringLike('https://minds.com/user/1', null);
    }

    public function it_should_not_like_an_entity_without_an_actor()
    {
        $this->setActivityPubServer('https://test');
        $this->shouldThrow(new \LogicException('The PubSub actor has not been specified.'))
            ->duringLike('https://minds.com/user/1', null);
    }

    public function it_should_send_like_to_pub_server()
    {
        $this->beConstructedWith($this->buildGuzzleMock());

        $this->setActivityPubServer('http://localhost');
        $this->setActor('testuser', 'https://minds.com/testuser');
        $this->like('https://minds.com/user/1', null)
            ->shouldBe(200);
    }
}
