<?php

namespace Identicon;

/**
 * @author Benjamin Laugueux <benjamin@yzalis.com>
 */
class Identicon
{

  /**
   * @var int The number of pixels
   */
    const NB_OF_PIXELS = 5;

    /**
     * @var int The border ratio
     */
    const BORDER_RATIO = 0.2;

    /**
     * @var int Opacity
     */
    const OPACITY = 100;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var integer
     */
    private $color;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var integer
     */
    private $pixelRatio;

    /**
     * @var array
     */
    private $arrayOfSquare = array();

    /**
     * Set the image size
     *
     * @param integer $size
     *
     * @return Identicon
     */
    public function setSize($size)
    {
        $this->size = $size;
        $this->pixelRatio = round($size / self::NB_OF_PIXELS);

        return $this;
    }

    /**
     * Get the image size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Generate a hash from the original string
     *
     * @param string $string
     *
     * @return Identicon
     */
    public function setString($string)
    {
        if (null === $string) {
            throw new \Exception('The string cannot be null.');
        }

        $this->hash = md5($string);

        $this->convertHashToArrayOfBoolean();

        return $this;
    }

    /**
     * Get the identicon string hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Convert the hash into an multidimensionnal array of boolean
     *
     * @return Identicon
     */
    private function convertHashToArrayOfBoolean()
    {
        preg_match_all('/(\w)(\w)/', $this->hash, $chars);
        foreach ($chars[1] as $i => $char) {
            if ($i % 3 == 0) {
                $this->arrayOfSquare[$i/3][0] = $this->convertHexaToBoolean($char);
                $this->arrayOfSquare[$i/3][4] = $this->convertHexaToBoolean($char);
            } elseif ($i % 3 == 1) {
                $this->arrayOfSquare[$i/3][1] = $this->convertHexaToBoolean($char);
                $this->arrayOfSquare[$i/3][3] = $this->convertHexaToBoolean($char);
            } else {
                $this->arrayOfSquare[$i/3][2] = $this->convertHexaToBoolean($char);
            }
            ksort($this->arrayOfSquare[$i/3]);
        }

        $this->color[0] = hexdec(array_pop($chars[1]))*16;
        $this->color[1] = hexdec(array_pop($chars[1]))*16;
        $this->color[2] = hexdec(array_pop($chars[1]))*16;

        return $this;
    }

    /**
     * Convert an heaxecimal number into a boolean
     *
     * @param string $hexa
     *
     * @return boolean
     */
    private function convertHexaToBoolean($hexa)
    {
        return (bool) intval(round(hexdec($hexa)/10));
    }

    /**
     *
     *
     * @return array
     */
    public function getArrayOfSquare()
    {
        return $this->arrayOfSquare;
    }

    /**
     * Generate a $x x $y image filled in with $color
     *
     */
    function imageCreateColor($x, $y, $color) {

        $im = imagecreatetruecolor($x, $y);
        $c = imagecolorallocate($im, $color[0], $color[1], $color[2]);
        imagefill($im, 0, 0, $c);

        return $im;
    }

    /**
     * Generate the Identicon image
     *
     * @param string  $string
     * @param integer $size
     * @param array of colors (hexa or rgb)
     * @param string $color Background color (hexa or rgb)
     */
    public function generateImage($string, $size, $colors = array(),  $colorBackground = null )
    {
        $this->setString($string);
        $drawableSize = $size - (self::BORDER_RATIO * $size);
        $this->setSize($drawableSize);

        /** Prepare the image */
        if ( !empty($colors) ) {
            $color = $colors[ rand( 0, count($colors)-1  ) ];
            $color = $this->transformHexaColorToRGBColor($color);
            $this->setColor($color);
        }

        $image = $this->imageCreateColor($drawableSize, $drawableSize, $this->color);

        /** Apply background color */
        if ( is_null($colorBackground)) {
          $colorBackground = '#ffffff';
        }
        $colorBackground = $this->transformHexaColorToRGBColor($colorBackground);

        $bgColor = imagecolorallocate($image, $colorBackground[0], $colorBackground[1], $colorBackground[2]);

        // draw the content
        foreach ($this->arrayOfSquare as $lineKey => $lineValue) {
            foreach ($lineValue as $colKey => $colValue) {
              if (true === $colValue) {
                  imagefilledrectangle($image, $colKey * $this->pixelRatio, $lineKey * $this->pixelRatio, ($colKey + 1) * $this->pixelRatio, ($lineKey + 1) * $this->pixelRatio, $bgColor);
              }
            }
        }

        /** Prepare the container image with given dimensions */
        $containerImage = $this->imageCreateColor($size, $size, $colorBackground);

        $margin = ( $size - ( (1 - self::BORDER_RATIO) * $size) ) / 2;
        imagecopymerge($containerImage, $image, $margin, $margin, 0, 0, $drawableSize, $drawableSize, self::OPACITY);

        return imagepng($containerImage);

    }

    /**
     * Set the image color
     *
     * @param string|array $color The color in hexa (6 chars) or rgb array
     *
     * @return Identicon
     */
    public function setColor($color)
    {
        if (is_array($color)) {
            $this->color[0] = $color[0];
            $this->color[1] = $color[1];
            $this->color[2] = $color[2];
        } else {
            if (false !== strpos($color, '#')) {
                $color = substr($color, 1);
            }
            $this->color[0] = hexdec(substr($color, 0, 2));
            $this->color[1] = hexdec(substr($color, 2, 2));
            $this->color[2] = hexdec(substr($color, 4, 2));
        }

        return $this;
    }

    /**
     * Get the color
     *
     * @return arrray
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Display an Identicon image
     *
     * @param string  $string
     * @param integer $size
     * @param array of colors (hexa or rgb)
     * @param string $color Background color (hexa or rgb)
     */
    public function displayImage($string, $size = 64, $colors = array(),  $colorBackground = null )
    {
        header("Content-Type: image/png");
        $this->generateImage($string, $size, $colors, $colorBackground);
    }


    /**
     * Transform hexadecimal color into RGB array
     *
     * @return arrray
     */
    public function transformHexaColorToRGBColor($hexaColor)
    {
        if (is_array($hexaColor)) {
             $rgbColor = $hexaColor;
        } else {
            if (false !== strpos($hexaColor, '#')) {
                $hexaColor = substr($hexaColor, 1);
            }
            $rgbColor[0] = hexdec(substr($hexaColor, 0, 2));
            $rgbColor[1] = hexdec(substr($hexaColor, 2, 2));
            $rgbColor[2] = hexdec(substr($hexaColor, 4, 2));
        }

        return $rgbColor;
    }

    /**
     * Get an Identicon PNG image data
     *
     * @param string  $string
     * @param integer $size
     * @param array of colors (hexa or rgb)
     * @param string $color Background color (hexa or rgb)
     *
     * @return string
     */
    public function getImageData($string, $size = 64, $colors = array(),  $colorBackground = null )
    {
        ob_start();
        $this->generateImage($string, $size, $colors, $colorBackground);
        $imageData = ob_get_contents();
        ob_end_clean();

        return $imageData;
    }

    /**
     * Get an Identicon PNG image data
     *
     * @param string  $string
     * @param integer $size
     * @param array of colors (hexa or rgb)
     * @param string $color Background color (hexa or rgb)
     *
     * @return string
     */
    public function getImageDataUri($string, $size = 64, $colors = array(),  $colorBackground = null )
    {
        return sprintf('data:image/png;base64,%s', base64_encode($this->getImageData($string, $size, $colors, $colorBackground)));
    }
}
