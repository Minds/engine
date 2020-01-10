<?php
/**
 * Index
 * @author edgebal
 */

namespace Minds\Core\Front;

use Minds\Core\Config\Exported;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Domain as ProDomain;
use Minds\Core\Pro\Settings;
use Minds\Core\SEO\Manager;

class Index
{
    /** @var Exported */
    protected $configExported;

    /** @var ProDomain */
    protected $proDomain;

    /** @var string[] */
    protected $meta = [];

    /** @var string[] */
    protected $head = [];

    /** @var string[] */
    protected $tail = [];

    /** @var string */
    protected $context = 'app';

    /** @var string */
    protected $title = '';

    /**
     * Index constructor.
     * @param Exported $configExported
     * @param ProDomain $proDomain
     */
    public function __construct(
        $configExported = null,
        $proDomain = null
    ) {
        $this->configExported = $configExported ?: Di::_()->get('Config\Exported');
        $this->proDomain = $proDomain ?: Di::_()->get('Pro\Domain');

        $this->build();
    }

    public function build(): void
    {
        $this->meta = [];
        $this->head = [];
        $this->tail = [];

        //

        /** @var Settings|null $pro */
        $pro = $this->proDomain->lookup($_SERVER['HTTP_HOST'] ?? null);

        // Config variable

        $minds = $this->configExported->export();

        if ($pro) {
            $minds['pro'] = $pro;
        }

        // Title

        $this->title = 'Minds';

        if ($pro) {
            $this->title = $pro->getTitle() ?: $pro->getDomain();
        }

        // Favicons

        $icons = [
            [
                'type' => 'image/svg',
                'href' => "{$minds['cdn_assets_url']}assets/logos/bulb.svg",
            ],
            [
                'rel' => 'apple-touch-icon',
                'type' => 'image/png',
                'href' => "{$minds['cdn_assets_url']}assets/logos/bulb-apple-touch-icon.png",
                'sizes' => '180x180',
            ],
            [
                'type' => 'image/png',
                'href' => "{$minds['cdn_assets_url']}assets/logos/bulb-32x32.png",
                'sizes' => '32x32',
            ],
            [
                'type' => 'image/png',
                'href' => "{$minds['cdn_assets_url']}assets/logos/bulb-16x16.png",
                'sizes' => '16x16',
            ],
        ];

        if ($pro) {
            $icons = [
                [
                    'type' => 'image/jpeg',
                    'href' => $this->proDomain->getIcon($pro),
                ],
            ];
        }

        foreach ($icons as $icon) {
            $attrs = [];

            foreach (array_merge(['rel' => 'icon'], $icon) as $key => $value) {
                $attrs[] = sprintf("%s=%s", $key, htmlspecialchars($value));
            }

            $this->meta[] = sprintf("<link %s />", implode(' ', $attrs));
        }

        // SEO Meta + Title Override

        $meta = Manager::get();

        foreach ($meta as $name => $content) {
            $name = htmlspecialchars($name);
            $content = htmlspecialchars($content);

            if ($name === 'title') {
                $this->title = $content;
                continue;
            }

            $nameAttr = 'name';

            if (
                strpos($name, ":") !== false &&
                strpos($name, "smartbanner") === false
            ) {
                // Attributes with a colon that are not smartbanner
                // should use property="<name>"
                $nameAttr = 'property';
            }

            $this->meta[] = sprintf(
                "<meta %s=\"%s\" content=\"%s\" />",
                $nameAttr,
                $name,
                $content
            );
        }

        // Head

        if ($pro) {
            $this->head[] = sprintf(
                "<!-- Minds Pro: %s -->\n%s\n<!-- End -->",
                $pro->getUserGuid(),
                $pro->getCustomHead() ?: '<!-- (no custom head) -->'
            );
        }

        // Tail

        $this->tail[] = sprintf("<script>window.Minds = %s;</script>", json_encode($minds));
    }

    /**
     * @return string
     */
    public function getMetaHtml(): string
    {
        return PHP_EOL . implode(PHP_EOL, $this->meta) . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getHeadHtml(): string
    {
        return PHP_EOL . implode(PHP_EOL, $this->head) . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getTailHtml(): string
    {
        return PHP_EOL . implode(PHP_EOL, $this->tail) . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
