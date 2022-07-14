<?php

namespace Minds\Core\Email\V2\Common;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\I18n\Translator;
use Minds\Core\Markdown\Markdown;

class Template
{
    protected $template;
    protected $template_path;
    protected $emailStyles;
    protected $data = [];
    protected $body;
    protected $loadFromFile = true;
    protected $useMarkdown = false;

    /** @var Markdown */
    protected $markdown;

    /** @var Translator */
    protected $translator;

    /** @var Config */
    protected $config;

    /**
     * Constructor.
     *
     * @param Markdown $markdown
     * @param Config $config
     * @param EmailStyles $emailStyles
     * @param Translator $translator
     */
    public function __construct(
        $markdown = null,
        $config = null,
        $emailStyles = null,
        $translator = null,
        private ?EmailStylesV2 $emailStylesV2 = null
    ) {
        $this->markdown = $markdown ?: new Markdown();
        $this->emailStyles = $emailStyles ?: Di::_()->get('Email\V2\Common\EmailStyles');
        $this->emailStylesV2 ??= Di::_()->get('Email\V2\Common\EmailStylesV2');
        $this->config = $config ?: Di::_()->get('Config');
        $this->data['site_url'] = $this->config->get('site_url') ?: 'https://www.minds.com/';
        $this->data['cdn_assets_url'] = $this->config->get('cdn_assets_url') ?: 'https://cdn-assets.minds.com/front/dist/';
        $this->data['cdn_url'] = $this->config->get('cdn_url') ?: 'https://cdn.minds.com/';

        $this->translator = $translator ?: Di::_()->get('I18n\Translator');
        $this->set('translator', $this->translator);
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template = 'default')
    {
        $this->template = $this->findTemplate($template);
        if (!$this->template) {
            $this->template = __MINDS_ROOT__.'/Core/Email/V2/Common/default.tpl';
        }

        return $this;
    }

    /**
     * @param $template
     * @param bool $fromFile
     * @return $this
     */
    public function setBody($template, $fromFile = true)
    {
        $this->body = $fromFile ? $this->findTemplate($template) : $template;
        $this->loadFromFile = (bool) $fromFile;

        return $this;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale)
    {
        $this->translator->setLocale($locale ?? "en");

        return $this;
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Get the underlying data for the template.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function toggleMarkdown($value)
    {
        $this->useMarkdown = (bool) $value;

        return $this;
    }

    /**
     * Sets a data key to be used within templates.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (!is_array($key)) {
            $this->data[$key] = $value;

            return $this;
        }

        foreach ($key as $singleKey => $value) {
            $this->data[$singleKey] = $value;
        }

        return $this;
    }

    /**
     * Find a template from a path.
     *
     * @param string $template
     *
     * @return string|null
     */
    protected function findTemplate($template)
    {
        //relative paths
        if (strpos($template, './') === 0 || strpos($template, '../') === 0) {
            //relative path!

            $trace = debug_backtrace();
            $traceFile = $trace[1]['file'];
            $parts = explode('/', $traceFile);
            array_pop($parts);
            $dir = implode('/', $parts);

            $template = substr($template, 1);
            $file = $dir.$template;

            if (file_exists($file)) {
                return $file;
            }
        }

        if (strpos($template, '/') !== 0) {
            $template = __MINDS_ROOT__.'/Core/Email/V2/Common/'.$template;
        }

        if (file_exists($template)) {
            return $template;
        }

        return;
    }

    /**
     * Renders template.
     * @return string
     */
    public function render()
    {
        $body = $this->loadFromFile ? $this->compile($this->body) : $this->body;
        if ($this->useMarkdown) {
            $body = $this->markdown->text($body);
        }
        $template = $this->compile($this->template, ['body' => $body]);

        return $template;
    }

    /**
     * Compiles a file by injecting variables and executing PHP code.
     *
     * @param string $file
     * @param array  $vars
     *

     * @return string
     */
    protected function compile($file, $vars = [])
    {
        $vars = array_merge($this->data, $vars);
        $emailStyles = $this->emailStyles;
        $emailStylesV2 = $this->emailStylesV2 ?? Di::_()->get('Email\V2\Common\EmailStylesV2');

        ob_start();

        include $file;

        $contents = ob_get_contents();

        ob_end_clean();

        return $contents;
    }

    /**
     * Prevent sending translator to queue
     * @return void
     */
    public function __sleep()
    {
        $this->set('translator', null);
        return [ 'template', 'template_path', 'emailStyles', 'data', 'body', 'loadFromFile', 'useMarkdown', 'emailStylesV2' ];
    }

    public function __wakeup()
    {
        $this->markdown = new Markdown();
        $this->translator = Di::_()->get('I18n\Translator');
        $this->set('translator', $this->translator);
    }
}
