<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use PhpSpec\ObjectBehavior;

class MetadataSpec extends ObjectBehavior
{
    public function it_should_build_from_metascraper_data()
    {
        $data = [
            'url' => 'url',
            'description' => 'description',
            'title' => 'title',
            'author' => 'author',
            'image' => 'image',
            'logo' => 'logo',
            'iframe' => 'iframe'
        ];

        $this->fromMetascraperData($data);

        $this->getUrl()->shouldBe('url');
        $this->getDescription()->shouldBe('description');
        $this->getTitle()->shouldBe('title');
        $this->getAuthor()->shouldBe('author');
        $this->getImage()->shouldBe('image');
        $this->getLogo()->shouldBe('logo');
        $this->getIframe()->shouldBe('iframe');
    }

    public function it_should_export()
    {
        $data = [
            'url' => 'url',
            'description' => 'description',
            'title' => 'title',
            'author' => 'author',
            'image' => 'image',
            'logo' => 'logo',
            'iframe' => 'iframe'
        ];

        $this->fromMetascraperData($data);

        $this->export()->shouldBe([
            'url' => 'url',
            'meta' => [
                'description' => 'description',
                'title' => 'title',
                'author' => 'author'
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
            'html' => 'iframe'
        ]);
    }
}
