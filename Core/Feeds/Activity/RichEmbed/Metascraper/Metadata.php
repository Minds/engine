<?php

namespace Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use JsonSerializable;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * Metadata object - holds metadata from Metascraper Server.
 *
 * @method string|null getUrl()
 * @method self setUrl(string $url)
 * @method string|null getDescription()
 * @method self setDescription(string $description)
 * @method string|null getTitle()
 * @method self setTitle(string $title)
 * @method string|null getAuthor()
 * @method self setAuthor(string $author)
 * @method string|null getImage()
 * @method self setImage(string $image)
 * @method string|null getLogo()
 * @method self setLogo(string $logo)
 * @method string|null getIframe()
 * @method self setIframe(string $iframe)
 */
class Metadata implements ExportableInterface, JsonSerializable
{
    use MagicAttributes;

    /** @var string|null url of site. */
    protected ?string $url;

    /** @var string|null description from metadata. */
    protected ?string $description;

    /** @var string|null title from metadata. */
    protected ?string $title;

    /** @var string|null author from metadata. */
    protected ?string $author;

    /** @var string|null image url from metadata. */
    protected ?string $image;

    /** @var string|null logo url from metadata. */
    protected ?string $logo;

    /** @var string|null iframe html from metadata. */
    protected ?string $iframe;

    /**
     * Build class from data provided by Metascraper Server.
     * @param array $data - data from Metascraper Server.
     * @return self
     */
    public function fromMetascraperData(array $data): self
    {
        $this->setUrl($data['url'] ?? '')
            ->setDescription($data['description'] ?? '')
            ->setTitle($data['title'] ?? '')
            ->setAuthor($data['author'] ?? '')
            ->setImage($data['image'] ?? '')
            ->setLogo($data['logo'] ?? '')
            ->setIframe($data['iframe'] ?? '');
        return $this;
    }

    /**
     * Provide exported class for serialization.
     * @return mixed - serialized class.
     */
    public function jsonSerialize(): mixed
    {
        return $this->export();
    }

    /**
     * Export class in a format for clients to digest.
     * @param array $extras - noop.
     * @return array exported data for client digestion.
     */
    public function export(array $extras = []): array
    {
        return [
            'url' => $this->url,
            'meta' => [
                'description' => $this->description,
                'title' => $this->title,
                'author' => $this->author
            ],
            'links' => [
                'thumbnail' => [
                    [
                        'href' => $this->image
                    ]
                ],
                'icon' => [
                    [
                        'href' => $this->logo
                    ]
                ]
            ],
            'html' => $this->iframe
        ];
    }
}
