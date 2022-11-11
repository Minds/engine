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
    /**
     * This value is the root size. We scale font sizes based on the actual width required.
     * @var int
     */
    const BASE_WIDTH = 500;

    /** @var int */
    const WIDTH_TO_HEIGHT_RATIO = 2;

    /** @var int $width */
    protected $width;

    /** @var int $height */
    protected $height;

    /** @var string $text */
    protected $text;

    /** @var string $username */
    protected $username;

    /** @var string $fontFile */
    protected $fontFile = './Assets/fonts/Roboto-Medium.ttf';

    /** @var string $fontFileBold */
    protected $fontFileBold = './Assets/fonts/Roboto-Black.ttf';

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
        $this->height = $this->width / self::WIDTH_TO_HEIGHT_RATIO;

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
        $imageWidth = $this->scaledSize(38);
        $imageHeight = $this->scaledSize(14);

        $logo = new \Imagick('./Assets/logos/white.png');
        $logo->resizeImage($imageWidth, $imageHeight, \Imagick::FILTER_BOX, 1);

        $this->canvas->compositeImage($logo, \Imagick::COMPOSITE_DEFAULT, ($this->width / 2) - ($imageWidth / 2), $this->scaledSize(32));

        return $this;
    }


    /**
     * Add text to canvas
     * @return Annotate
     */
    private function addTextToCanvas()
    {
        // TODO remove rich-embed urls??

        $textColor = new \ImagickPixel('white');

        $textCharLength = strlen($this->text);

        $baseFontSize = 16;
        
        if ($textCharLength > 180) {
            $rem = 1;
        } elseif ($textCharLength > 100) {
            $rem = 1.35;
        } elseif ($textCharLength > 50) {
            $rem = 1.7;
        } else {
            $rem = 2;
        }

        /**
         * Set up draw object for writing text
         */
        $draw = new \ImagickDraw();
        $draw->setFillColor($textColor);
        $draw->setFont($this->fontFileBold);
        $draw->setFontSize($this->scaledSize($baseFontSize * $rem)); // TODO make size dynamic
        $draw->setGravity(\Imagick::GRAVITY_CENTER);

        $lineHeight = $this->scaledSize(($baseFontSize * $rem) + ($rem * 2));

        if (strlen($this->text) >= 280) {
            $this->text = substr($this->text, 0, 280) . '...';
        }

        $wrappedText = wordwrap($this->text, 52 / $rem);
        $textToLines = explode("\n", $wrappedText);
        $numberOfLines = count($textToLines);
        $y = $numberOfLines > 1 ? ($lineHeight * ($numberOfLines - 1)) * -0.5  : 0;


        /**
         * Annotate canvas with text
         */
        foreach ($textToLines as $line) {
            $this->canvas->annotateImage($draw, 0, $y, 0, $line);
            $y += $lineHeight;
        }

        return $this;
    }

    /**
     * Add username to canvas
     * @return Annotate
     */
    private function addUsernameToCanvas()
    {
        $textColor = new \ImagickPixel('white');

        /**
         * Set up draw object for writing text
         */
        $draw = new \ImagickDraw();
        $draw->setFillColor($textColor);
        $draw->setFont($this->fontFile);
        $draw->setFontSize($this->scaledSize(12));
        $draw->setGravity(\Imagick::GRAVITY_SOUTH);

        /**
         * Annotate canvas with text
         */
        $paddingBottom = $this->scaledSize(32);

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

    /**
     * Calculates the sizes based off our base size
     * @param int $size
     * @return int
     */
    protected function scaledSize($size): int
    {
        $scale = $this->width / self::BASE_WIDTH;
        return $size * $scale;
    }
}
