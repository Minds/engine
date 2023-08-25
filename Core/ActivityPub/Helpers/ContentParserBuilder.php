<?php
namespace Minds\Core\ActivityPub\Helpers;

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
     * Makes any links or user tags into html links
     */
    public static function format(string $input): string
    {
        $output =  str_replace("\n", '<br />', $input);

        $siteUrl = Di::_()->get('Config')->get('site_url');
                
        $output = Autolink::create()
            ->setExternal(false)
            ->setNoFollow(false)
            ->setUrlBaseUser($siteUrl)
            ->setUrlBaseHash($siteUrl . 'search?f=top&t=all&q=')
            ->setUrlBaseCash($siteUrl . 'search?f=top&t=all&q=')
            ->setHashtagClass('hashtag')
            ->setUsernameClass('mention')
            ->setUsernameIncludeSymbol(true)
            ->autoLink($input);

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
