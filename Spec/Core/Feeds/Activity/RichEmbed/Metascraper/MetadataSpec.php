<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use PhpSpec\ObjectBehavior;

class MetadataSpec extends ObjectBehavior
{
    public function it_should_build_from_metascraper_data()
    {
        $data = [
            'url' => 'canonical_url',
            'description' => 'description',
            'title' => 'title',
            'author' => 'author',
            'image' => 'image',
            'logo' => 'logo',
            'iframe' => 'iframe',
            'publisher' => 'publisher',
        ];

        $this->fromMetascraperData($data, 'url');

        $this->getCanonicalUrl()->shouldBe('canonical_url');
        $this->getUrl()->shouldBe('url');
        $this->getDescription()->shouldBe('description');
        $this->getTitle()->shouldBe('title');
        $this->getAuthor()->shouldBe('author');
        $this->getImage()->shouldBe('image');
        $this->getLogo()->shouldBe('logo');
        $this->getIframe()->shouldBe('iframe');
        $this->getPublisher()->shouldBe('publisher');
    }

    public function it_should_build_from_metascraper_data_with_canonical_as_url()
    {
        $data = [
            'url' => 'url',
            'description' => 'description',
            'title' => 'title',
            'author' => 'author',
            'image' => 'image',
            'logo' => 'logo',
            'iframe' => 'iframe',
            'publisher' => 'publisher',
        ];

        $this->fromMetascraperData($data);

        $this->getUrl()->shouldBe('url');
        $this->getCanonicalUrl()->shouldBe('url');
        $this->getDescription()->shouldBe('description');
        $this->getTitle()->shouldBe('title');
        $this->getAuthor()->shouldBe('author');
        $this->getImage()->shouldBe('image');
        $this->getLogo()->shouldBe('logo');
        $this->getIframe()->shouldBe('iframe');
        $this->getPublisher()->shouldBe('publisher');
    }

    public function it_should_export()
    {
        $data = [
            'url' => 'canonical_url',
            'description' => 'description',
            'title' => 'title',
            'author' => 'author',
            'image' => 'image',
            'logo' => 'logo',
            'iframe' => 'iframe',
            'date' => date('c', strtotime('midnight')),
            'datePublished' => date('c', strtotime('midnight 2 days ago')),
            'publisher' => 'publisher',
        ];

        $this->fromMetascraperData($data, 'url');

        $this->export()->shouldBe([
            'url' => 'url',
            'meta' => [
                'description' => 'description',
                'title' => 'title',
                'author' => 'author',
                'canonical_url' => 'canonical_url'
            ],
            'links' => [
                'thumbnail' => [
                    [
                        'href' => 'image'
                    ]
                ],
                'icon' => [
                    [
                        'href' => 'logo'
                    ]
                ]
            ],
            'date' => date('c', strtotime('midnight 2 days ago')),
            'html' => 'iframe',
            'publisher' => 'publisher',
        ]);
    }

    public function it_should_have_date_if_published_date_not_found()
    {
        $data = [
            'url' => 'canonical_url',
            'description' => 'description',
            'title' => 'title',
            'author' => 'author',
            'image' => 'image',
            'logo' => 'logo',
            'iframe' => 'iframe',
            'publisher' => 'publisher',
            'date' => date('c', strtotime('midnight')),
        ];

        $this->fromMetascraperData($data, 'url');

        $this->export()->shouldBe([
            'url' => 'url',
            'meta' => [
                'description' => 'description',
                'title' => 'title',
                'author' => 'author',
                'canonical_url' => 'canonical_url'
            ],
            'links' => [
                'thumbnail' => [
                    [
                        'href' => 'image'
                    ]
                ],
                'icon' => [
                    [
                        'href' => 'logo'
                    ]
                ]
            ],
            'date' => date('c', strtotime('midnight')),
            'html' => 'iframe',
            'publisher' => 'publisher',
        ]);
    }
}
