<?php
/**
 * XSS Sanitizer
 */
namespace Minds\Core\Security;

use Minds\Core;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Interfaces\XSSRule;

class XSS
{
    private $rules = [];
    private $allowed = [];
    /** @var Config */
    protected $config;

    public function __construct(
        XSS\TagsRule $tagsRule = null,
        XSS\GenericRule $genericRule = null,
        XSS\UriSchemeRule $uriSchemeRule = null,
    ) {
        $this->init(
            $tagsRule ?? new XSS\TagsRule,
            $genericRule ?? new XSS\GenericRule,
            $uriSchemeRule ?? new XSS\UriSchemeRule,
        );
    }

    /**
     * Initialise our basic rules
     * @return void
     */
    private function init(
        XSS\TagsRule $tagsRule,
        XSS\GenericRule $genericRule,
        XSS\UriSchemeRule $uriSchemeRule,
    ) {
        $this->setAllowed();
        $this->addRule($tagsRule);
        $this->addRule($genericRule);
        $this->addRule($uriSchemeRule);
    }

    /**
     * Add rules to check
     * @param XSSRule $rules
     * @return $this
     */
    private function addRule(XSSRule $rule)
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Set the allowed attributes and tag names
     * @param array $allowed
     * @return $this
     */
    public function setAllowed($allowed = [])
    {
        $this->allowed = array_merge($allowed, [
          '<div>', '<a>', '<b>', '<i>', '<u>', '<em>', '<strong>', '<ul>', '<ol>', '<li>', '<p>', '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>', '<blockquote>', '<br>', //tag names
          '<sub>', '<sup>', '<span>', //more tag names
          '<img>', '<video>', '<iframe>', //tag names
          'a=href', '*=src', '*=width', '*=height', '*=scrolling', '*=style', '*=class', '*=data-oembed-url', '*=align', '*=alt', //attibute names
          '::http', '::https', '::*', //scheme protocols
        ]);

        return $this;
    }

    /**
     * Clean a html block of possibel XSS tags
     * @param string $string
     * @return string
     */
    public function clean($string)
    {
        foreach ($this->rules as $rule) {
            $string = $rule
              ->setString($string)
              ->setAllowed($this->allowed)
              ->clean()
              ->getString();
        }

        return $string;
    }
}
