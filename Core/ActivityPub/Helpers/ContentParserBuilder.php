<?php
namespace Minds\Core\ActivityPub\Helpers;

use Minds\Common\Regex;
use Minds\Core\Di\Di;
use Twitter\Text\Extractor;
use Twitter\Text\Autolink;
use Soundasleep\Html2Text;

class ContentParserBuilder
{
    /**
     * Returns all urls that are found in a string
     * @return string[]
     */
    public static function getUrls(string $input): array
    {
        return Extractor::create()->extractUrls($input);
    }

    /**
     * Returns all urls that are found in a string
     * @return string[]
     */
    public static function getMentions(string $input): array
    {
        preg_match_all(Regex::AT, $input, $matches);

        /** @var string[] */
        $mentions = [];

        foreach ($matches[0] as $match) {
            $mentions[] = ltrim($match, ' ');
        }

        return $mentions;
    }

    /**
     * Makes any links or user tags into html links
     */
    public static function format(string $input): string
    {
        $output =  str_replace("\n", '<br />', $input);

        $siteUrl = Di::_()->get('Config')->get('site_url');

        $autoLink = Autolink::create()
            ->setExternal(false)
            ->setNoFollow(false)
            ->setUrlBaseUser($siteUrl)
            ->setUrlBaseHash($siteUrl . 'search?f=top&t=all&q=')
            ->setUrlBaseCash($siteUrl . 'search?f=top&t=all&q=')
            ->setHashtagClass('u-url hashtag')
            ->setUsernameClass('u-url mention')
            ->setUsernameIncludeSymbol(true);

        $output = $autoLink->autoLink($input);

        // Fix for webfinger
        // foreach (self::getMentions($input) as $mention) {
        //     if (substr_count($mention, '@') < 2) {
        //         continue;
        //     }
        //     $href = $siteUrl . $mention;
        //     $output = str_replace($mention, "<a class=\"u-url mention\" href=\"$href\">$mention</a>", $output);
        // }

        return $output;
    }

    /**
     * Converts HTML content to plaintext
     */
    public static function sanitize(string $input): string
    {
        $output = Html2Text::convert($input, [
            'ignore_errors' => true,
            'drop_links' => true,
        ]);

        $output = strip_tags($output);

        return $output;
    }
}
