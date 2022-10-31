<?php
namespace Minds\Core\Media\Imagick;

/**
 * Used for creating twitter cards for supermind
 * cross-platform posting
 *
 * Cards have a predetermind aspect ratio and
 * use the supermind gradient background
 *
 * Text is truncated if it's too long.
 * Font size is larger for shorter posts and vice-versa.
 *
 * TODO: handle adding media to canvas
 */
class Annotate extends AbstractImagick
{
    /** @var int $width */
    protected $width;

    /** @var int $height */
    protected $height;

    /** @var int $widthToHeightRatio */
    protected $widthToHeightRatio = 2;

    /** @var string $text */
    protected $text;

    /** @var string $username */
    protected $username;

    /** @var string $fontFile */
    protected $fontFile = './Assets/fonts/Roboto-Medium.ttf';

    /** @var \Imagick $canvas */
    protected $canvas;

    /** @var \Imagick $output */
    protected $output;


    /**
     * @return \Imagick
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * We only use width because we have a predetermined aspect ratio
     * @param int $width
     * @return Annotate
     */
    private function setDimensions($width)
    {
        if ($width < 300) {
            throw new \Exception('Width must be larger to accommodate text annotation.');
        }

        $this->width = $width;
        $this->height = $this->width / $this->widthToHeightRatio;

        return $this;
    }

    /**
     * @param string $text
     * @return Annotate
     */
    private function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param string $username
     * @return Annotate
     */
    private function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Create gradient canvas with supermind colors
     * @param int $width
     * @return Annotate
     */
    private function createGradientCanvas()
    {
        // TODO emulate tri-color weighted logic so gradient
        // better matches supermind branding css:
        // linear-gradient(96deg,#0038ff 3.14%,#6c37e7 56.68%,#ac0091 112.49%);
        // see: https://legacy.imagemagick.org/discourse-server/viewtopic.php?t=19812

        $transparent = new \ImagickPixel('transparent');

        /**
         * Create transparent canvas
         *
         * We reverse height and width because we're going to
         * rotate the canvas 90deg to match the designs
         */
        $this->canvas = new \Imagick();
        $this->canvas->newImage($this->height, $this->width, $transparent, 'png');

        /**
         * Create gradient pseudo image
         *
         * (also with reversed dimensions)
         */
        $gradient = new \Imagick();
        $gradient->newPseudoImage($this->height, $this->width, 'gradient:#0038ff-#ac0091');

        /**
         * Add gradient onto canvas
         */
        $this->canvas->compositeImage($gradient, \Imagick::COMPOSITE_OVER, 0, 0);

        /**
         * Rotate canvas so orientation & gradient match designs
         */
        $this->canvas->rotateImage($transparent, -90);

        return $this;
    }

    /**
     * Add Minds logo to canvas
     * @return Annotate
     */
    private function addLogoToCanvas()
    {
        // TODO
        return $this;
    }


    /**
     * Add text to canvas
     * @return Annotate
     */
    private function addTextToCanvas()
    {
        // TODO remove rich-embed urls??
        // TODO $this->truncate($this->text, $maxChars = 280);

        $textColor = new \ImagickPixel('white');

        /**
         * Set up draw object for writing text
         */
        $draw = new \ImagickDraw();
        $draw->setFillColor($textColor);
        $draw->setFont($this->fontFile);
        $draw->setFontSize(60); // TODO make size dynamic
        $draw->setGravity(\Imagick::GRAVITY_CENTER);

        /**
         * Annotate canvas with text
         */
        $this->canvas->annotateImage($draw, 0, 0, 0, $this->text);

        return $this;
    }

    /**
     * Add username to canvas
     * @return Annotate
     */
    private function addUsernameToCanvas()
    {
        // TODO $this->truncate($this->username, $maxChars = 80);
        $textColor = new \ImagickPixel('white');

        /**
         * Set up draw object for writing text
         */
        $draw = new \ImagickDraw();
        $draw->setFillColor($textColor);
        $draw->setFont($this->fontFile);
        $draw->setFontSize(24);
        $draw->setGravity(\Imagick::GRAVITY_SOUTH);

        /**
         * Annotate canvas with text
         */
        $paddingBottom = ($this->width / $this->widthToHeightRatio) / 5;

        $this->canvas->annotateImage($draw, 0, $paddingBottom, 0, '@' . $this->username);

        return $this;
    }

    /**
     * Creates a 2:1 image with text/logo/username superimposed
     * over the supermind gradient background
     *
     * @param int $width of the output image
     * @param string $text to be added over the gradient background
     * @param string $username of the text author
     * @return Annotate
     * @throws \Exception
     */
    public function annotate(int $width, string $text, string $username)
    {
        if (!$width || !$text || !$username) {
            throw new \Exception('Width, text and username are required to create an annotated image');
        }

        $this->setDimensions($width);
        $this->setText($text);
        $this->setUsername($username);

        $this->createGradientCanvas();

        $this->addLogoToCanvas();
        $this->addTextToCanvas();
        $this->addUsernameToCanvas();

        $this->output = $this->canvas;

        return $this;
    }
}
