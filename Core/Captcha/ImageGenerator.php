<?php
namespace Minds\Core\Captcha;

class ImageGenerator
{
    /** @var int */
    protected $width = 250;
    
    /** @var int */
    protected $height = 100;

    /** @var string */
    protected $text;

    /**
     * Set the width
     * @param int $width
     * @return self
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }
    /**
     * Set the height
     * @param int $height
     * @return self
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }
    /**
     * Set the text to output
     * @param string $text
     * @return self
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Outputs the captcha image
     * @return string
     */
    public function build(): string
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // Slight grey background
        $backgroundColor = imagecolorallocate($image, 240, 240, 240);
        
        // Builds the image background
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $backgroundColor);

        // Set the line thickness
        imagesetthickness($image, 3);
 
        // Dark grey lines
        $lineColor = imagecolorallocate($image, 74, 74, 74);
        $numberOfLines = rand(4, 10);
 
        for ($i = 0; $i < $numberOfLines; $i++) {
            imagesetthickness($image, rand(1, 3));
            imageline($image, 0, rand() % $this->height, $this->width, rand() % $this->height, $lineColor);
        }
 
        for ($i = 0; $i< $this->width * 4; $i++) {
            $pixelColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imagesetpixel($image, rand() % $this->width, rand() % $this->height, $pixelColor);
        }
 
        $font = __MINDS_ROOT__ . '/Assets/fonts/Roboto-Medium.ttf';

        $angle = rand(-6, 6);
        $size = rand($this->height * 0.25, $this->height * 0.55);
        $x = 10;
        $y = ($this->height / 2) + ($size / 2);
        $color = imagecolorallocate($image, 64, 64, 64);

        // Write the text to the image
        imagettftext($image, $size, $angle, $x, $y, $color, $font, $this->text);

        ob_start();
        imagepng($image);
        $imagedata = ob_get_clean();
        $base64 = base64_encode($imagedata);
        imagedestroy($image);
        
        return "data:image/png;base64,$base64";
    }
}
