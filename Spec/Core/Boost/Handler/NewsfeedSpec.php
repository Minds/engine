<?php

namespace Spec\Minds\Core\Boost\Handler;

use Minds\Core\Blogs\Blog;
use Minds\Core\Boost\Handler\Newsfeed;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;

class NewsfeedSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Newsfeed::class);
    }

    public function it_should_validate_entity(
        Activity $activity,
        Video $video,
        Image $image,
        Blog $blog,
        User $user
    ) {
        $this->validateEntity($activity)->shouldReturn(true);
        $this->validateEntity($video)->shouldReturn(true);
        $this->validateEntity($image)->shouldReturn(true);
        $this->validateEntity($blog)->shouldReturn(true);
        $this->validateEntity($user)->shouldReturn(false);
    }
}
