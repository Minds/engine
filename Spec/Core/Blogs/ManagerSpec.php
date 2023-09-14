<?php

namespace Spec\Minds\Core\Blogs;

use Minds\Core\Blogs\Blog;
use Minds\Core\Blogs\Delegates;
use Minds\Core\Blogs\Repository;
use Minds\Core\Config\Config;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Core\Security\SignedUri;
use Minds\Core\Security\Spam;
use Minds\Entities\Image;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Delegates\PaywallReview */
    protected $paywallReview;

    /** @var Delegates\Slug */
    protected $slug;

    /** @var Delegates\Feeds */
    protected $feeds;

    /** @var Spam */
    protected $spam;

    /** @var Delegates\Search */
    protected $search;

    /** @var PropagateProperties */
    protected $propagateProperties;

    /** @var SignedUri */
    protected $signedUri;

    /** @var Config */
    protected $config;

    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Logger */
    protected $logger;

    public function let(
        Repository $repository,
        Delegates\PaywallReview $paywallReview,
        Delegates\Slug $slug,
        Delegates\Feeds $feeds,
        Spam $spam,
        PropagateProperties $propagateProperties,
        SignedUri $signedUri,
        Config $config,
        EventsDispatcher $eventsDispatcher,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $repository,
            $paywallReview,
            $slug,
            $feeds,
            $spam,
            $propagateProperties,
            $signedUri,
            $config,
            $eventsDispatcher,
            $entitiesBuilder,
            $logger
        );

        $this->repository = $repository;
        $this->paywallReview = $paywallReview;
        $this->slug = $slug;
        $this->feeds = $feeds;
        $this->spam = $spam;
        $this->propagateProperties = $propagateProperties;
        $this->signedUri = $signedUri;
        $this->config = $config;
        $this->eventsDispatcher = $eventsDispatcher;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blogs\Manager');
    }

    public function it_should_get(Blog $blog)
    {
        $this->repository->get('10000000000000000000')
            ->shouldBeCalled()
            ->willReturn($blog);

        $this
            ->get('10000000000000000000')
            ->shouldReturn($blog);
    }

    public function it_should_get_with_legacy_guid(Blog $blog)
    {
        $migratedGuid = (new \GUID())->migrate(1);

        $this->repository->get($migratedGuid)
            ->shouldBeCalled()
            ->willReturn($blog);

        $this
            ->get(1)
            ->shouldReturn($blog);
    }

    public function it_should_get_next_blog_by_owner(Blog $blog, Blog $nextBlog)
    {
        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->repository->getList([
            'gt' => 5000,
            'limit' => 1,
            'user' => 1000,
            'reversed' => false,
        ])
            ->shouldBeCalled()
            ->willReturn([$nextBlog]);

        $this
            ->getNext($blog, 'owner')
            ->shouldReturn($nextBlog);
    }

    public function it_should_get_next_null_blog_by_owner(Blog $blog)
    {
        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn(5001);

        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->repository->getList([
            'gt' => 5001,
            'limit' => 1,
            'user' => 1000,
            'reversed' => false,
        ])
            ->shouldBeCalled()
            ->willReturn([]);

        $this
            ->getNext($blog, 'owner')
            ->shouldReturn(null);
    }

    public function it_should_throw_if_no_strategy_during_get_next(Blog $blog)
    {
        $this->repository->getList(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new \Exception('Unknown next strategy'))
            ->duringGetNext($blog, 'notimplemented');
    }

    public function it_should_add(Blog $blog)
    {
        $this->spam->check($blog)
            ->shouldBeCalled();

        $blog->getType()
            ->willReturn('object');

        $blog->getSubtype()
            ->willReturn('blog');

        $blog->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(9999);

        $blog->setTimeCreated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setTimeUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastSave(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->isDeleted()
            ->shouldBeCalled()
            ->willReturn(false);

        $blog->getUrn()
            ->willReturn('urn:blog:123');

        $this->slug->generate($blog)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->add($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feeds->index($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feeds->dispatch($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->paywallReview->queue($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->add($blog)
            ->shouldReturn(true);
    }

    public function it_should_update(Blog $blog)
    {
        $blog->isDirty('deleted')
            ->shouldBeCalled()
            ->willReturn(true);

        $blog->isDeleted()
            ->shouldBeCalled()
            ->willReturn(false);

        $blog->setTimeUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastSave(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->getUrn()
            ->willReturn('urn:blog:123');

        $this->slug->generate($blog)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->update($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->paywallReview->queue($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->propagateProperties->from($blog)->shouldBeCalled();
        $this
            ->update($blog)
            ->shouldReturn(true);
    }

    public function it_should_delete(Blog $blog)
    {
        $blog->getUrn()
            ->willReturn('urn:blog:123');

        $this->repository->delete($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feeds->remove($blog)
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->delete($blog)
            ->shouldReturn(true);
    }

    public function it_should_check_for_spam(Blog $blog, Spam $spam)
    {
        $spamUrl = 'movieblog.tumblr.com';

        $blog->getType()
            ->willReturn('object');

        $blog->getSubtype()
            ->willReturn('blog');

        $this->spam->check(Argument::any())->shouldBeCalled()->willReturn(true);
        $this->add($blog);
    }

    public function it_should_sign_images(
        Blog $blog,
        Image $image
    ) {
        $entityGuid = '1234567890112233';
        $blogOwnerGuid = '2234567890112233';
        $imageOwnerGuid = '2234567890112233';

        $imageUri = "http://localhost:8080/fs/v1/thumbnail/$entityGuid/xlarge";
        $signedImageUri = "$imageUri?jwtsig=123456";
        $body = '<img src="'.$imageUri.'">';
        $signedBody = '<img src="'.$signedImageUri.'">';

        $blog->getBody()
            ->shouldBeCalled()
            ->willReturn($body);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');

        $this->config->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');
        
        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn($image);

        $image->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($imageOwnerGuid);

        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($blogOwnerGuid);

        $this->signedUri->sign($imageUri)
            ->shouldBeCalled()
            ->willReturn($signedImageUri);

        $this->signImages($blog)->shouldBe($signedBody);
    }

    public function it_should_sign_images_but_not_sign_over_existing_jwtsigs(
        Blog $blog,
        Image $image
    ) {
        $entityGuid = '1234567890112233';
        $blogOwnerGuid = '2234567890112233';
        $imageOwnerGuid = '2234567890112233';

        $signiturelessImageUrl = "http://localhost:8080/fs/v1/thumbnail/$entityGuid/xlarge";
        $imageUri = "$signiturelessImageUrl?jwtsig=123456";
        $signedImageUri = "$imageUri?jwtsig=234567";
        $body = '<img src="'.$imageUri.'">';
        $signedBody = '<img src="'.$signedImageUri.'">';

        $blog->getBody()
            ->shouldBeCalled()
            ->willReturn($body);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');

        $this->config->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');
        
        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn($image);

        $image->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($imageOwnerGuid);

        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($blogOwnerGuid);

        $this->signedUri->sign($signiturelessImageUrl)
            ->shouldBeCalled()
            ->willReturn($signedImageUri);

        $this->signImages($blog)->shouldBe($signedBody);
    }

    public function it_should_NOT_sign_images_when_image_entity_cannot_be_found(
        Blog $blog
    ) {
        $entityGuid = '1234567890112233';
        $blogGuid = '2234567890112233';

        $imageUri = "http://localhost:8080/fs/v1/thumbnail/$entityGuid/xlarge";
        $body = '<img src="'.$imageUri.'">';

        $blog->getBody()
            ->shouldBeCalled()
            ->willReturn($body);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');

        $this->config->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');
        
        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn($blogGuid);

        $this->logger->warning(Argument::type('string'))
            ->shouldBeCalled();

        $this->signedUri->sign($imageUri)
            ->shouldNotBeCalled();

        $this->signImages($blog)->shouldBe($body);
    }

    public function it_should_NOT_sign_images_when_image_owner_is_not_blog_owner(
        Blog $blog,
        Image $image
    ) {
        $entityGuid = '1234567890112233';
        $blogOwnerGuid = '2234567890112233';
        $imageOwnerGuid = '3234567890112233';
        $blogGuid = '4234567890112233';

        $imageUri = "http://localhost:8080/fs/v1/thumbnail/$entityGuid/xlarge";
        $body = '<img src="'.$imageUri.'">';

        $blog->getBody()
            ->shouldBeCalled()
            ->willReturn($body);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');

        $this->config->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn('http://localhost:8080');
        
        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn($image);

        $image->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($imageOwnerGuid);

        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($blogOwnerGuid);

        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn($blogGuid);

        $this->logger->warning(Argument::type('string'))
            ->shouldBeCalled();

        $this->signedUri->sign($imageUri)
            ->shouldNotBeCalled();

        $this->signImages($blog)->shouldBe($body);
    }
}
