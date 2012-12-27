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
     * The image contents
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Generate the image
     */
    public function build($phrase = null, $width = 150, $height = 40, $font = null, $fingerprint = null)
    {
        if (null !== $fingerprint) {
            $this->fingerprint = $fingerprint;
            $this->useFingerprint = true;
        } else {
            $this->fingerprint = array();
            $this->useFingerprint = false;
        }

        if ($font === null) {
            $font = __DIR__ . '/Font/captcha.ttf';
        }

        if ($phrase === null) {
            $phrase = PhraseBuilder::build();
        }

        $i   = imagecreatetruecolor($width, $height);
        $col = imagecolorallocate($i, $this->rand(0, 110), $this->rand(0, 110), $this->rand(0, 110));

        imagefill($i, 0, 0, 0xFFFFFF);

        // Draw random lines
        for ($t = 0; $t < 10; $t++) {
            $tcol = imagecolorallocate($i, 100 + $this->rand(0, 150), 100 + $this->rand(0, 150), 100 + $this->rand(0, 150));
            $Xa   = $this->rand(0, $width);
            $Ya   = $this->rand(0, $height);
            $Xb   = $this->rand(0, $width);
            $Yb   = $this->rand(0, $height);
            imageline($i, $Xa, $Ya, $Xb, $Yb, $tcol);
        }

        // Write CAPTCHA text
        $size       = $width / strlen($phrase);
        $box        = imagettfbbox($size, 0, $font, $phrase);
        $textWidth  = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];

        imagettftext($i, $size, 0, ($width - $textWidth) / 2, ($height - $textHeight) / 2 + $size, $col, $font, $phrase);

        // Distort the image
        $X          = $this->rand(0, $width);
        $Y          = $this->rand(0, $height);
        $phase      = $this->rand(0, 10);
        $scale      = 1.3 + $this->rand(0, 10000) / 30000;
        $contents   = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $Vx = $x - $X;
                $Vy = $y - $Y;
                $Vn = sqrt($Vx * $Vx + $Vy * $Vy);

                if ($Vn != 0) {
                    $Vn2 = $Vn + 4 * sin($Vn / 8);
                    $nX  = $X + ($Vx * $Vn2 / $Vn);
                    $nY  = $Y + ($Vy * $Vn2 / $Vn);
                } else {
                    $nX = $X;
                    $nY = $Y;
                }
                $nY = $nY + $scale * sin($phase + $nX * 0.2);

                $p = $this->bilinearInterpolate($nX - floor($nX), $nY - floor($nY),
                    $this->getCol($i, floor($nX), floor($nY)),
                    $this->getCol($i, ceil($nX), floor($nY)),
                    $this->getCol($i, floor($nX), ceil($nY)),
                    $this->getCol($i, ceil($nX), ceil($nY)));

                if ($p == 0) {
                    $p = 0xFFFFFF;
                }

                imagesetpixel($contents, $x, $y, $p);
            }
        }

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
        imagejpeg($this->contents, null, $quality);

        return ob_get_clean();
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
    protected function rand($min, $max)
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
    protected function getCol($image, $x, $y)
    {
        $L = imagesx($image);
        $H = imagesy($image);
        if ($x < 0 || $x >= $L || $y < 0 || $y >= $H) {
            return 0xFFFFFF;
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

