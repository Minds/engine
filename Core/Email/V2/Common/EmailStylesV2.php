<?php

namespace Minds\Core\Email\V2\Common;

class EmailStylesV2
{
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
            "padding: 0;"
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
        "m-mainContent__paragraph" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "margin: 10px 20px 30px;",
            "font-size: 18px;",
            "line-height: 28px;",
            "color: #0a080b;"
        ],
        "m-mainContent__code" => [
            "font-family: 'Inter', Arial, sans-serif;",
            "margin: 10px 20px 30px;",
            "font-size: 48px;",
            "font-weight: 800;",
            "line-height: 70px;",
            "color: #0a080b;",
            "border: 1px dashed #666666;",
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
     * @return string[][]
     */
    private function getMergedStyleDefinitions(): array
    {
        return array_merge(
            self::MAIN_CONTENT,
            self::IMAGE,
            self::BUTTON,
            self::FOOTER,
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
