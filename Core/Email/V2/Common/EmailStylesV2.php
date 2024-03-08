<?php
namespace Minds\Core\Email\V2\Common;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

class EmailStylesV2
{
    /**
     * @param Config|null $config
     */
    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get(Config::class);
    }

    public function __wakeup() {
        // Re-initialize config after unserialization
        $this->config ??= Di::_()->get(Config::class);
    }

    private const MAIN_CONTENT = [
        "m-mainContent" => [
            "width:600px;",
            "background-color: #ffffff;"
        ],
        "m-mainContent__header" => [
            "padding:30px 0 0;"
        ],
        "m-mainContent__imageAltText" => [
            "color: #4a4a4a;",
            "font-family: 'Inter', Arial, sans-serif;",
            "text-align:center;",
            "font-weight:bold;",
            "font-size:24px;",
            "line-height:28px;",
            "text-decoration: none;",
            "padding: 0;",
            "margin-left: auto;",
            "margin-right: auto;"
        ],
        "m-mainContent__h1" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "margin: 20px 20px 40px 20px;",
            "font-size: 42px;",
            "line-height: 52px;",
            "text-align: center;",
            "color: #0a080b;",
            "font-weight: 700;"
        ],
        "m-mainContent__mainArticle" => [
            "padding: 30px 0 50px;",
        ],
        "m-mainContent__paragraphSubject" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "margin: 10px 20px 10px;",
            "font-size: 18px;",
            "line-height: 28px;",
            "color: #0a080b;"
        ],
        "m-mainContent__paragraph" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "margin: 10px 20px 30px;",
            "font-size: 18px;",
            "line-height: 28px;",
            "color: #0a080b;"
        ],
        "m-mainContent__paragraph--subtext" => [
            "margin: 10px 20px 30px;",
            "font-family: 'Inter', sans-serif;",
            "font-size: 16px;",
            "line-height: 28px;",
            "color: #0a080b;"
        ],
        "m-mainContent__signup_paragraph" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "margin: 25px 25px 95px;",
            "font-size: 14px;",
            "color: #000;"
        ],
        "m-mainContent__signup_paragraph--link" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "color: #4080D0;",
            "text-align:center;",
            "text-decoration: underline;",
            "cursor: pointer;",
        ],
        "m-mainContent__code" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "margin: 10px 20px 30px;",
            "font-size: 48px;",
            "font-weight: 800;",
            "line-height: 70px;",
            "color: #0a080b;",
            "border: 1px dashed #666666;",
        ],
        "m-mainContent__linkBox" => [
            "font-size: 18px;",
            "line-height: 28px;",
            "margin: 0 20px 30px;",
            "padding: 20px;",
            "background-color: rgb(238, 238, 238);",
            "font-family: monospace;",
            "word-break: break-word;",
            "text-decoration: underline;"
        ],
        "m-mainContent__linkBoxHref" => [
            "color: #0a080b;"
        ],
        "m-mainContent__standaloneLink" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "display: inline-block;",
            "margin: 30px 20px 0px;",
            "font-size: 18px;",
            "line-height: 20px;",
            "color: #0a080b;",
            "text-align:center;",
            "text-decoration: underline;"
        ],
        "m-mainContent__standaloneLink--noMargin" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "display: inline-block;",
            "font-size: 18px;",
            "line-height: 20px;",
            "color: #0a080b;",
            "text-align:center;",
            "text-decoration: underline;"
        ]
    ];

    private const IMAGE = [
        "light-img" => [],
        "dark-img" => [
            "display:none;",
            "overflow:hidden;",
            "width:0px;",
            "max-height:0px;",
            "max-width:0px;",
            "line-height:0px;",
            "visibility:hidden;"
        ]
    ];

    private const BUTTON = [
        "m-button" => [
            "background-color:#1B85D6;",
            "border-radius:50px;",
            "color:#ffffff;",
            "display:inline-block;",
            "font-family:sans-serif;",
            "font-size:22px;",
            "font-family: 'Inter', Arial, sans-serif;",
            "font-weight:800;",
            "line-height:40px;",
            "text-align:center;",
            "text-decoration:none;",
            "width:200px;",
            "-webkit-text-size-adjust:none;",
            "padding: 10px 25px;"
        ],
        "m-button-outlook" => [
            "height:60px;",
            "v-text-anchor:middle;",
            "width:200px;"
        ]
    ];

    private const FOOTER = [
        "m-footer" => [
            "padding:50px 0;"
        ],
        "m-footer__paragraph" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "font-size:14px;",
            "line-height:24px;",
            "mso-line-height-rule:exactly;",
            "color:#0a080b;",
            "margin-bottom:20px;"
        ],
        "m-footer__link" => [
            "color: #0a080b;",
            "text-decoration: underline;"
        ],
    ];

    /**
     * Returns overrides for styles based on tenant theme settings.
     * Currently used only for buttons
     * @return string[]
     */
    private function getThemeOverrides(): array
    {
        $themeOverrides = $this->config->get('theme_override');

        $modifiedStyles = [];

        if ($this->config->get('tenant_id') && is_array($themeOverrides)) {
            if (isset($themeOverrides['primary_color'])) {
                $modifiedStyles["background-color"] = "background-color:{$themeOverrides['primary_color']};";
            }

            if (isset($themeOverrides['color_scheme'])) {
                $textColor = $themeOverrides['color_scheme'] === 'DARK' ? '#000' : '#fff';
                // Important prevents email clients from overriding
                $modifiedStyles["color"] = "color:{$textColor} !important;";
            }
        }

        // Clone the default button styles
        $buttonStyles = self::BUTTON['m-button'];

        // Apply any modified styles to the clone
        foreach ($buttonStyles as &$style) {
            // Check if this style is for background-color or color,
            // and apply if a modification exists
            foreach ($modifiedStyles as $key => $value) {
                if (strpos($style, $key) !== false) {
                    $style = $value;
                    break;
                }
            }
        }

        return [
            "m-button" => $buttonStyles
        ];
    }

    /**
     * @return string[]
     */
    private function getMergedStyleDefinitions(): array
    {
        $themeOverrides = $this->getThemeOverrides();

        return array_merge(
            self::MAIN_CONTENT,
            self::IMAGE,
            self::BUTTON,
            self::FOOTER,
            $themeOverrides // This must be the last array item for overrides to apply
        );
    }

    /**
     * Returns the string representing the inline style requested
     * @param string[] $keys
     * @return string
     */
    public function getStyles(array $keys): string
    {
        $styles = array_intersect_key($this->getMergedStyleDefinitions(), array_flip($keys));

        array_walk($styles, function (&$currentStyles, $key) {
            $currentStyles = implode("", $currentStyles);
        });

        return 'style="' . implode('', $styles) . '"';
    }

}
