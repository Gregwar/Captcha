<?php

namespace Gregwar\Captcha;

/**
 * Builds a new captcha image
 * Uses the fingerprint parameter, if one is passed, to generate the same image
 *
 * @author Gregwar <g.passault@gmail.com>
 * @author Jeremy Livingston <jeremy.j.livingston@gmail.com>
 */
class CaptchaBuilder
{
    /**
     * @var array
     */
    protected $fingerprint = array();

    /**
     * @var bool
     */
    protected $useFingerprint = false;

    /**
     * @var resource
     */
    protected $contents = null;

    /**
     * @var string
     */
    protected $phrase = null;

    /**
     * The image contents
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Temporary dir, for OCR check
     */
    public $tempDir = 'temp/';

    /**
     * Gets the captcha phrase
     */
    public function getPhrase()
    {
        return $this->phrase;
    }

    public function __construct($phrase = null)
    {
        if ($phrase === null) {
            $phrase = PhraseBuilder::build();
        }

        $this->phrase = $phrase;
    }

    /**
     * Instantiation
     */
    public static function create($phrase = null)
    {
        return new self($phrase);
    }

    /**
     * Returns all the scrambling functions
     */
    public function getScramblingFunctions()
    {
        $self = $this;

        return array(
            // Random lines
            function($image, $width, $height) use ($self) {
                $tcol = imagecolorallocate($image, $self->rand(100, 255), $self->rand(100, 255), $self->rand(100, 255));
                $Xa   = $self->rand(0, $width);
                $Ya   = $self->rand(0, $height);
                $Xb   = $self->rand(0, $width);
                $Yb   = $self->rand(0, $height);
                imageline($image, $Xa, $Ya, $Xb, $Yb, $tcol);
            },
            // Random circles
            function($image, $width, $height) use ($self) {
                $tcol = imagecolorallocate($image, $self->rand(100, 255), $self->rand(100, 255), $self->rand(100, 255));
                $Xa   = $self->rand(0, $width);
                $Ya   = $self->rand(0, $height);
                $R   = $self->rand(0, min($width, $height));
                imagearc($image, $Xa, $Ya, $R, $R, 0, 360, $tcol);
            },
            // Add some noise
            function($image, $width, $height) use ($self) {
                // Noises the image
                $square = $width*$height;
                for ($t = 0; $t < $square/100; $t++) {
                    $tcol = imagecolorallocate($image, $self->rand(0, 255), $self->rand(0, 255), $self->rand(0, 255));
                    imagesetpixel($image, $self->rand(0, $width), $self->rand(0, $height), $tcol);
                }
            }
        );
    }

    public function getScramblingFunction()
    {
        $functions = $this->getScramblingFunctions();

        return $functions[$this->rand(0, count($functions)-1)];
    }

    /**
     * Apply some post effects
     */
    protected function postEffect($image)
    {
        if (!function_exists('imagefilter')) {
            return;
        }

        // Negate ?
        if ($this->rand(0, 1) == 0) {
            imagefilter($image, IMG_FILTER_NEGATE);
        }

        // Edge ?
        if ($this->rand(0, 10) == 0) {
            imagefilter($image, IMG_FILTER_EDGEDETECT);
        } 
        
        // Contrast
        imagefilter($image, IMG_FILTER_CONTRAST, $this->rand(-50, 10));
        
        // Colorize
        if ($this->rand(0, 5) == 0) {
            imagefilter($image, IMG_FILTER_COLORIZE, $this->rand(-80, 50), $this->rand(-80, 50), $this->rand(-80, 50));
        }
    }

    /**
     * Writes the phrase on the image
     */
    protected function writePhrase($image, $phrase, $font, $width, $height)
    {
        // Gets the text size and start position
        $size = $width / strlen($phrase) - $this->rand(0,3) - 1;
        $box = imagettfbbox($size, 0, $font, $phrase);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2 + $size;

        // Write the letters one by one, with random angle
        for ($i=0; $i<strlen($phrase); $i++) {
            $col = imagecolorallocate($image, $this->rand(0, 150), $this->rand(0, 150), $this->rand(0, 150));
            $box = imagettfbbox($size, 0, $font, $phrase[$i]);
            $w = $box[2] - $box[0];
            imagettftext($image, $size, $this->rand(-15, 15), $x, $y + $this->rand(-5, 5), $col, $font, $phrase[$i]);
            $x += $w;
        }
    }

    /**
     * Try to read the code against an OCR
     */
    public function isOCRReadable()
    {
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0755, true);
        }

        $tempj = $this->tempDir . uniqid('captcha', true) . '.jpg';
        $tempp = $this->tempDir . uniqid('captcha', true) . '.pgm';

        $this->save($tempj);
        shell_exec("convert $tempj $tempp");
        $value = trim(strtolower(shell_exec("ocrad $tempp")));

        @unlink($tempj);
        @unlink($tempp);

        return ($value == $this->phrase);
    }

    /**
     * Builds while the code is readable against an OCR
     */
    public function buildAgainstOCR($width = 150, $height = 40, $font = null, $fingerprint = null)
    {
        do {
            $this->build();
        } while ($this->isOCRReadable());
    }

    /**
     * Generate the image
     */
    public function build($width = 150, $height = 40, $font = null, $fingerprint = null)
    {
        if (null !== $fingerprint) {
            $this->fingerprint = $fingerprint;
            $this->useFingerprint = true;
        } else {
            $this->fingerprint = array();
            $this->useFingerprint = false;
        }

        if ($font === null) {
            $font = __DIR__ . '/Font/captcha'.$this->rand(0, 5).'.ttf';
        }

        $image   = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, $this->rand(180, 255), $this->rand(180, 255), $this->rand(180, 255));
        $this->background = $bg;
        imagefill($image, 0, 0, $bg);
        
        // Write CAPTCHA text
        $this->writePhrase($image, $this->phrase, $font, $width, $height);

        // Apply effects
        $square = $width * $height;
        $effects = $this->rand($square/1000, $square/500);
        for ($e = 0; $e < $effects; $e++) {
            $function = $this->getScramblingFunction();
            for ($i=0; $i<$square/5000; $i++) {
                $function($image, $width, $height);    
            }
        }

        // Distort the image
        $X          = $this->rand(0, $width);
        $Y          = $this->rand(0, $height);
        $phase      = $this->rand(0, 10);
        $scale      = 1.0 + $this->rand(0, 10000) / 30000;
        $contents   = imagecreatetruecolor($width, $height);

        // Distort the image
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $Vx = $x - $X;
                $Vy = $y - $Y;
                $Vn = sqrt($Vx * $Vx + $Vy * $Vy);

                if ($Vn != 0) {
                    $Vn2 = $Vn + 4 * sin($Vn / 10);
                    $nX  = $X + ($Vx * $Vn2 / $Vn);
                    $nY  = $Y + ($Vy * $Vn2 / $Vn);
                } else {
                    $nX = $X;
                    $nY = $Y;
                }
                $nY = $nY + $scale * sin($phase + $nX * 0.2);

                $p = $this->bilinearInterpolate($nX - floor($nX), $nY - floor($nY),
                    $this->getCol($image, floor($nX), floor($nY), $bg),
                    $this->getCol($image, ceil($nX), floor($nY), $bg),
                    $this->getCol($image, floor($nX), ceil($nY), $bg),
                    $this->getCol($image, ceil($nX), ceil($nY), $bg));

                if ($p == 0) {
                    $p = 0xFFFFFF;
                }

                imagesetpixel($contents, $x, $y, $p);
            }
        }
        
        // Post effects
        $this->postEffect($contents);

        $this->contents = $contents;

        return $this;
    }

    /**
     * Saves the Captcha to a jpeg file
     */
    public function save($filename, $quality = 80)
    {
        imagejpeg($this->contents, $filename, $quality);
    }

    /**
     * Gets the image contents
     */
    public function get($quality = 80)
    {
        ob_start();
        $this->output($quality);

        return ob_get_clean();
    }

    /**
     * Outputs the image
     */
    public function output($quality = 80)
    {
        imagejpeg($this->contents, null, $quality);
    }

    /**
     * @return array
     */
    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    /**
     * Returns a random number or the next number in the
     * fingerprint
     */
    public function rand($min, $max)
    {
        if (!is_array($this->fingerprint)) {
            $this->fingerprint = array();
        }

        if ($this->useFingerprint) {
            $value = current($this->fingerprint);
            next($this->fingerprint);
        } else {
            $value = mt_rand($min, $max);
            $this->fingerprint[] = $value;
        }

        return $value;
    }

    /**
     * @param $x
     * @param $y
     * @param $nw
     * @param $ne
     * @param $sw
     * @param $se
     *
     * @return int
     */
    protected function bilinearInterpolate($x, $y, $nw, $ne, $sw, $se)
    {
        list($r0, $g0, $b0) = $this->getRGB($nw);
        list($r1, $g1, $b1) = $this->getRGB($ne);
        list($r2, $g2, $b2) = $this->getRGB($sw);
        list($r3, $g3, $b3) = $this->getRGB($se);

        $cx = 1.0 - $x;
        $cy = 1.0 - $y;

        $m0 = $cx * $r0 + $x * $r1;
        $m1 = $cx * $r2 + $x * $r3;
        $r  = (int)($cy * $m0 + $y * $m1);

        $m0 = $cx * $g0 + $x * $g1;
        $m1 = $cx * $g2 + $x * $g3;
        $g  = (int)($cy * $m0 + $y * $m1);

        $m0 = $cx * $b0 + $x * $b1;
        $m1 = $cx * $b2 + $x * $b3;
        $b  = (int)($cy * $m0 + $y * $m1);

        return ($r << 16) | ($g << 8) | $b;
    }

    /**
     * @param $image
     * @param $x
     * @param $y
     *
     * @return int
     */
    protected function getCol($image, $x, $y, $background)
    {
        $L = imagesx($image);
        $H = imagesy($image);
        if ($x < 0 || $x >= $L || $y < 0 || $y >= $H) {
            return $background;
        }

        return imagecolorat($image, $x, $y);
    }

    /**
     * @param $col
     *
     * @return array
     */
    protected function getRGB($col)
    {
        return array(
            (int)($col >> 16) & 0xff,
            (int)($col >> 8) & 0xff,
            (int)($col) & 0xff,
        );
    }
}

