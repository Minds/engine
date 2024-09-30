<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Models;

use Minds\Core\MultiTenant\Bootstrap\Models\ExtractedMetadata;
use PhpSpec\ObjectBehavior;

class ExtractedMetadataSpec extends ObjectBehavior
{
    public function let()
    {
        $logoUrl = 'https://example.minds.com/logo.png';
        $description = 'description';
        $publisher = 'Minds';

        $this->beConstructedWith($logoUrl, $description, $publisher);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ExtractedMetadata::class);
    }

    public function it_should_return_logo_url()
    {
        $this->getLogoUrl()->shouldReturn('https://example.minds.com/logo.png');
    }

    public function it_should_return_description()
    {
        $this->getDescription()->shouldReturn('description');
    }

    public function it_should_return_publisher()
    {
        $this->getPublisher()->shouldReturn('Minds');
    }

    public function it_should_set_logo_url()
    {
        $newLogoUrl = 'https://example.minds.com/new-logo.png';
        $this->setLogoUrl($newLogoUrl);
        $this->getLogoUrl()->shouldReturn($newLogoUrl);
    }

    public function it_should_set_description()
    {
        $newDescription = 'description2';
        $this->setDescription($newDescription);
        $this->getDescription()->shouldReturn($newDescription);
    }

    public function it_should_set_publisher()
    {
        $newPublisher = 'Minds2';
        $this->setPublisher($newPublisher);
        $this->getPublisher()->shouldReturn($newPublisher);
    }
}
