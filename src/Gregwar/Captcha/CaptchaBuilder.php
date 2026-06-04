<?php

namespace Gregwar\Captcha;

use Exception;
use GdImage;
use InvalidArgumentException;
use LogicException;
use function imagecolorallocate;
use function imagettfbbox;
use function imagettftext;

/**
 * Builds a new captcha image
 * Uses the fingerprint parameter, if one is passed, to generate the same image
 *
 * @author Gregwar <g.passault@gmail.com>
 * @author Jeremy Livingston <jeremy.j.livingston@gmail.com>
 */
class CaptchaBuilder implements CaptchaBuilderInterface
{
    /** @var int[] $fingerprint */
    protected array $fingerprint = [];

    protected bool $useFingerprint = false;

    /** @var int[]|null $textColor */
    protected ?array $textColor = null;

    /** @var int[]|null $lineColor */
    protected ?array $lineColor = null;

    /** @var int[]|null $backgroundColor */
    protected ?array $backgroundColor = null;

    protected int $bgAlpha = 0;

    /** @var string[] $backgroundImages */
    protected array $backgroundImages = [];

    protected ?GdImage $contents = null;

    protected ?string $phrase = null;

    protected ?PhraseBuilderInterface $builder = null;

    protected bool $distortion = true;

    /**
     * The maximum number of lines to draw in front of
     * the image. null - use default algorithm
     */
    protected ?int $maxFrontLines = null;

    /**
     * The maximum number of lines to draw behind
     * the image. null - use default algorithm
     */
    protected ?int $maxBehindLines = null;

    /**
     * The maximum angle of char
     */
    protected int $maxAngle = 8;

    /**
     * The maximum offset of char
     */
    protected int $maxOffset = 5;

    /**
     * Is the interpolation enabled?
     */
    protected bool $interpolation = true;

    /**
     * Ignore all effects
     */
    protected bool $ignoreAllEffects = false;

    protected bool $scatterEffect = true;

    /**
     * Allowed image types for the background images
     *
     * @var string[]
     */
    protected array $allowedBackgroundImageTypes = ['image/png', 'image/jpeg', 'image/gif'];

    protected string $imageType = "jpeg";

    /**
     * The image contents
     */
    public function getContents(): ?GdImage
    {
        return $this->contents;
    }

    /**
     * Enable/Disables the interpolation
     */
    public function setInterpolation(bool $interpolate = true): static
    {
        $this->interpolation = $interpolate;

        return $this;
    }

    /**
     * Temporary dir, for OCR check
     */
    public string $tempDir = '';

    public function __construct(?string $phrase = null, ?PhraseBuilderInterface $builder = null)
    {
        $this->builder = (!$builder)
            ? new PhraseBuilder()
            : $builder;
        $this->phrase = is_string($phrase) ? $phrase : $this->builder->build($phrase);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'captcha' . DIRECTORY_SEPARATOR;
    }

    public function setImageType(?string $imageType = null): static
    {
        $imageType = is_string($imageType) ? strtolower($imageType) : '';
        if (in_array($imageType, ['png', 'jpeg', 'gif'])) {
            $this->imageType = $imageType;
        }

        return $this;
    }

    public function getImageType(): string
    {
        return $this->imageType;
    }

    /**
     * Setting the phrase
     */
    public function setPhrase(?string $phrase = null): void
    {
        $this->phrase = $phrase;
    }

    /**
     * Enables/disable distortion
     */
    public function setDistortion(bool|int $distortion): static
    {
        $this->distortion = (bool) $distortion;

        return $this;
    }

    /**
     * Enables/disable scatter effect - Only applies to PHP 7.4+
     */
    public function setScatterEffect(bool $scatterEffect): static
    {
        $this->scatterEffect = $scatterEffect;

        return $this;
    }

    public function setMaxBehindLines(?int $maxBehindLines): static
    {
        $this->maxBehindLines = $maxBehindLines;

        return $this;
    }

    public function setMaxFrontLines(?int $maxFrontLines): static
    {
        $this->maxFrontLines = $maxFrontLines;

        return $this;
    }

    public function setMaxAngle(int $maxAngle): static
    {
        $this->maxAngle = $maxAngle;

        return $this;
    }

    public function setMaxOffset(int $maxOffset): static
    {
        $this->maxOffset = $maxOffset;

        return $this;
    }

    /**
     * Gets the captcha phrase
     */
    public function getPhrase(): ?string
    {
        return $this->phrase;
    }

    /**
     * Returns true if the given phrase is good
     */
    public function testPhrase(string $phrase): bool
    {
        return $this->getPhrase() && $this->builder && $this->builder->niceize($phrase) == $this->builder->niceize($this->getPhrase());
    }

    /**
     * Instantiation
     */
    public static function create(?string $phrase = null): CaptchaBuilder
    {
        return new self($phrase);
    }

    /**
     * Sets the text color to use
     */
    public function setTextColor(int $r, int $g, int $b): static
    {
        $this->textColor = [$r, $g, $b];

        return $this;
    }

    /**
     * Sets the background color to use
     */
    public function setBackgroundColor(int $r, int $g, int $b): static
    {
        $this->backgroundColor = [$r, $g, $b];

        return $this;
    }

    /**
     * @param int $alpha 0 to 127, 127 is completely transparent
     * @return $this
     */
    public function setBackgroundAlpha(int $alpha): static
    {
        if ($alpha < 0 || $alpha > 127) {
            throw new InvalidArgumentException('Argument $alpha must be an integer between 0 and 127.');
        }

        if ($this->getImageType() !== 'png') {
            throw new LogicException('You can only set transparency on PNG images, call setImageType(\'png\')');
        }

        $this->bgAlpha = $alpha;

        return $this;
    }

    public function getBackgroundAlpha(): int
    {
        return $this->bgAlpha;
    }

    /**
     * Sets the line color to use
     */
    public function setLineColor(int $r, int $g, int $b): static
    {
        $this->lineColor = [$r, $g, $b];

        return $this;
    }

    /**
     * Sets the ignoreAllEffects value
     */
    public function setIgnoreAllEffects(bool $ignoreAllEffects): static
    {
        $this->ignoreAllEffects = $ignoreAllEffects;

        return $this;
    }

    /**
     * Sets the list of background images to use (one image is randomly selected)
     * @param string[] $backgroundImages
     */
    public function setBackgroundImages(array $backgroundImages): static
    {
        $this->backgroundImages = $backgroundImages;

        return $this;
    }

    /**
     * Draw lines over the image
     */
    protected function drawLine(GdImage $image, int $width = 150, int $height = 40, ?int $tcol = null): void
    {
        if ($this->lineColor === null) {
            $red = $this->rand(100, 255);
            $green = $this->rand(100, 255);
            $blue = $this->rand(100, 255);
        } else {
            $red = $this->lineColor[0];
            $green = $this->lineColor[1];
            $blue = $this->lineColor[2];
        }

        if ($tcol === null) {
            $tcol = imagecolorallocate($image, $red, $green, $blue);
        }

        if ($this->rand(0, 1)) { // Horizontal
            $Xa   = $this->rand(0, (int)($width / 2));
            $Ya   = $this->rand(0, $height);
            $Xb   = $this->rand((int)($width / 2), $width);
            $Yb   = $this->rand(0, $height);
        } else { // Vertical
            $Xa   = $this->rand(0, $width);
            $Ya   = $this->rand(0, (int)($height / 2));
            $Xb   = $this->rand(0, $width);
            $Yb   = $this->rand((int)($height / 2), $height);
        }
        imagesetthickness($image, $this->rand(1, 3));
        imageline($image, $Xa, $Ya, $Xb, $Yb, (int)$tcol);
    }

    /**
     * Apply some post effects
     */
    protected function postEffect(GdImage $image, ?int $bg = null): void
    {
        if (!function_exists('imagefilter')) {
            return;
        }

        if ($this->backgroundColor != null || $this->textColor != null) {
            return;
        }

        // Scatter/Noise - Added in PHP 7.4
        $scattered = false;
        if (defined('IMG_FILTER_SCATTER')) {
            if ($this->scatterEffect && $this->rand(0, 3) != 0 && $bg != null) {
                $scattered = true;
                imagefilter($image, IMG_FILTER_SCATTER, 0, 2, [$bg]);
            }
        }

        // Negate ?
        if ($this->rand(0, 1) == 0) {
            imagefilter($image, IMG_FILTER_NEGATE);
        }

        // Edge ?
        if (!$scattered && $this->rand(0, 10) == 0) {
            imagefilter($image, IMG_FILTER_EDGEDETECT);
        }

        // Contrast
        imagefilter($image, IMG_FILTER_CONTRAST, $this->rand(-50, 10));

        // Colorize
        if (!$scattered && $this->rand(0, 5) == 0) {
            imagefilter($image, IMG_FILTER_COLORIZE, $this->rand(-80, 50), $this->rand(-80, 50), $this->rand(-80, 50));
        }
    }

    /**
     * Writes the phrase on the image
     */
    protected function writePhrase(GdImage $image, ?string $phrase, string $font, int $width, int $height): ?int
    {
        $length = mb_strlen((string)$phrase);
        if ($length === 0 || !$phrase) {
            return imagecolorallocate($image, 0, 0, 0) ?: null;
        }

        // Gets the text size and start position
        $size = (int) round($width / $length) - $this->rand(0, 3) - 1;
        $box = imagettfbbox($size, 0, $font, $phrase);
        if (!$box) {
            return null;
        }
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) round(($width - $textWidth) / 2);
        $y = (int) round(($height - $textHeight) / 2) + $size;

        if (!$this->textColor) {
            $textColor = [$this->rand(0, 150), $this->rand(0, 150), $this->rand(0, 150)];
        } else {
            $textColor = $this->textColor;
        }
        $col = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
        if ($col !== false) {
            // Write the letters one by one, with random angle
            for ($i = 0; $i < $length; $i++) {
                $symbol = mb_substr($phrase, $i, 1);
                $box = imagettfbbox($size, 0, $font, $symbol);
                if (!$box) {
                    return null;
                }
                $w = $box[2] - $box[0];
                $angle = $this->rand(-$this->maxAngle, $this->maxAngle);
                $offset = $this->rand(-$this->maxOffset, $this->maxOffset);
                imagettftext($image, $size, $angle, $x, $y + $offset, $col, $font, $symbol);
                $x += $w;
            }
        }

        return $col ?: null;
    }

    /**
     * Try to read the code against an OCR
     * @throws Exception
     */
    public function isOCRReadable(): bool
    {
        if (!is_dir($this->tempDir) && !mkdir($this->tempDir, 0755, true)) {
            throw new Exception('Failed to create temporary directory for OCR check: ' . $this->tempDir);
        }

        $tempj = $this->tempDir . uniqid('captcha', true) . '.jpg';
        $tempp = $this->tempDir . uniqid('captcha', true) . '.pgm';

        $this->save($tempj);

        shell_exec("convert " . escapeshellarg($tempj) . " " . escapeshellarg($tempp));
        if (!file_exists($tempp)) {
            if (!file_exists($tempj)) {
                @unlink($tempj);
            }

            throw new Exception('isOCRReadable failed to convert file for testing.');
        }

        $ocradOutput = shell_exec("ocrad " . escapeshellarg($tempp));
        $value = '';
        if ($ocradOutput) {
            $value = trim(strtolower($ocradOutput));
        }

        @unlink($tempj);
        @unlink($tempp);

        return $this->testPhrase($value);
    }

    /**
     * Builds while the code is readable against an OCR
     * @param int[] $fingerprint
     * @throws Exception
     */
    public function buildAgainstOCR(int $width = 150, int $height = 40, ?string $font = null, ?array $fingerprint = null): void
    {
        do {
            $this->build($width, $height, $font, $fingerprint);
        } while ($this->isOCRReadable());
    }

    /**
     * Generate the image
     * @param int[] $fingerprint
     * @throws Exception
     */
    public function build(int $width = 150, int $height = 40, ?string $font = null, ?array $fingerprint = null): static
    {
        if (null !== $fingerprint) {
            $this->fingerprint = $fingerprint;
            $this->useFingerprint = true;
        } else {
            $this->fingerprint = [];
            $this->useFingerprint = false;
        }

        if ($font === null) {
            $font = __DIR__ . '/Font/captcha' . $this->rand(0, 5) . '.ttf';
        }

        $bg = 0;
        if (empty($this->backgroundImages) && $width > 0 && $height > 0) {
            // if background images list is not set, use a color fill as a background
            $image = imagecreatetruecolor($width, $height);
            if ($this->backgroundColor == null) {
                $bg = imagecolorallocatealpha(
                    $image,
                    $this->rand(200, 255),
                    $this->rand(200, 255),
                    $this->rand(200, 255),
                    $this->bgAlpha
                );
            } else {
                $color = $this->backgroundColor;
                $bg = imagecolorallocatealpha($image, $color[0], $color[1], $color[2], $this->bgAlpha);
            }

            imagefill($image, 0, 0, $bg ?: 0);
            imagesavealpha($image, true);
        } else {
            // use a random background image
            $randomBackgroundImage = $this->backgroundImages[rand(0, count($this->backgroundImages) - 1)];

            $imageType = $this->validateBackgroundImage($randomBackgroundImage);

            $image = $this->createBackgroundImageFromType($randomBackgroundImage, $imageType);
        }
        if (!$image) {
            throw new LogicException('Failed to create background image');
        }
        // Apply effects
        if (!$this->ignoreAllEffects) {
            $square = $width * $height;
            $effects = $this->rand((int)($square / 3000), (int)($square / 2000));

            // set the maximum number of lines to draw in front of the text
            if ($this->maxBehindLines != null && $this->maxBehindLines > 0) {
                $effects = min($this->maxBehindLines, $effects);
            }

            if ($this->maxBehindLines !== 0) {
                for ($e = 0; $e < $effects; $e++) {
                    $this->drawLine($image, $width, $height);
                }
            }
        }

        // Write CAPTCHA text
        $color = $this->writePhrase($image, $this->phrase, $font, $width, $height);

        // Apply effects
        if (!$this->ignoreAllEffects) {
            $square = $width * $height;
            $effects = $this->rand((int)($square / 3000), (int)($square / 2000));

            // set the maximum number of lines to draw in front of the text
            if ($this->maxFrontLines != null && $this->maxFrontLines > 0) {
                $effects = min($this->maxFrontLines, $effects);
            }

            if ($this->maxFrontLines !== 0) {
                for ($e = 0; $e < $effects; $e++) {
                    $this->drawLine($image, $width, $height, $color);
                }
            }
        }

        // Distort the image
        if ($this->distortion && !$this->ignoreAllEffects) {
            $image = $this->distort($image, $width, $height, $bg ?: 0);
        }

        // Post effects
        if (!$this->ignoreAllEffects && $image) {
            $this->postEffect($image, $bg ?: 0);
        }

        $this->contents = $image;

        return $this;
    }

    /**
     * Distorts the image
     */
    public function distort(GdImage $image, int $width, int $height, int $bg = 0): ?GdImage
    {
        $contents = imagecreatetruecolor($width, $height);
        imagefill($contents, 0, 0, $bg);
        imagesavealpha($contents, true);
        $X          = $this->rand(0, $width);
        $Y          = $this->rand(0, $height);
        $phase      = $this->rand(0, 10);
        $scale      = 1.1 + $this->rand(0, 10000) / 30000;
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $Vx = $x - $X;
                $Vy = $y - $Y;
                $Vn = sqrt($Vx * $Vx + $Vy * $Vy);

                if ($Vn != 0) {
                    $Vn2 = $Vn + 4 * sin($Vn / 30);
                    $nX  = $X + ($Vx * $Vn2 / $Vn);
                    $nY  = $Y + ($Vy * $Vn2 / $Vn);
                } else {
                    $nX = $X;
                    $nY = $Y;
                }
                $nY = $nY + $scale * sin($phase + $nX * 0.2);

                if ($this->interpolation) {
                    $p = $this->interpolate(
                        $nX - floor($nX),
                        $nY - floor($nY),
                        $this->getCol($image, floor($nX), floor($nY), $bg),
                        $this->getCol($image, ceil($nX), floor($nY), $bg),
                        $this->getCol($image, floor($nX), ceil($nY), $bg),
                        $this->getCol($image, ceil($nX), ceil($nY), $bg)
                    );
                } else {
                    $p = $this->getCol($image, round($nX), round($nY), $bg);
                }

                if ($p == 0) {
                    $p = $bg;
                }

                imagesetpixel($contents, $x, $y, $p);
            }
        }

        return $contents ?: null;
    }

    /**
     * Saves the Captcha to file
     * @throws Exception
     */
    public function save(?string $filename = null, int $quality = 90): void
    {
        $imageType = $this->getImageType();
        if (!$this->contents) {
            throw new Exception('No image generated');
        }
        switch ($imageType) {
            case "png":
                imagepng($this->contents, $filename, (int)($quality / 10)); // quality 0-9
                break;
            case "gif":
                imagegif($this->contents, $filename);
                break;
            default:
                imagejpeg($this->contents, $filename, $quality); // quality 0-100
                break;
        }
    }

    /**
     * Gets the image GD
     */
    public function getGd(): ?GdImage
    {
        return $this->contents;
    }

    /**
     * Gets the image contents
     */
    public function get(int $quality = 90): string
    {
        ob_start();
        try {
            $this->output($quality);
        } catch (Exception) {
        }

        return ob_get_clean() ?: '';
    }

    /**
     * Gets the HTML inline base64
     */
    public function inline(int $quality = 90): string
    {
        return sprintf('data:image/%s;base64,%s', $this->getImageType(), base64_encode($this->get($quality)));
    }

    /**
     * Outputs the image
     * @throws Exception
     */
    public function output(int $quality = 90): void
    {
        $this->save(null, $quality);
    }

    /**
     * @return int[]
     */
    public function getFingerprint(): array
    {
        return $this->fingerprint;
    }

    /**
     * Returns a random number or the next number in the
     * fingerprint
     */
    protected function rand(int $min, int $max): int
    {
        if ($this->useFingerprint) {
            $value = current($this->fingerprint);
            next($this->fingerprint);
        } else {
            $value = mt_rand($min, $max);
            $this->fingerprint[] = $value;
        }

        return (int)$value;
    }

    protected function interpolate(float $x, float $y, int $nw, int $ne, int $sw, int $se): int
    {
        list($r0, $g0, $b0) = $this->getRGB($nw);
        list($r1, $g1, $b1) = $this->getRGB($ne);
        list($r2, $g2, $b2) = $this->getRGB($sw);
        list($r3, $g3, $b3) = $this->getRGB($se);

        $cx = 1.0 - $x;
        $cy = 1.0 - $y;

        $m0 = $cx * $r0 + $x * $r1;
        $m1 = $cx * $r2 + $x * $r3;
        $r  = (int) ($cy * $m0 + $y * $m1);

        $m0 = $cx * $g0 + $x * $g1;
        $m1 = $cx * $g2 + $x * $g3;
        $g  = (int) ($cy * $m0 + $y * $m1);

        $m0 = $cx * $b0 + $x * $b1;
        $m1 = $cx * $b2 + $x * $b3;
        $b  = (int) ($cy * $m0 + $y * $m1);

        return ($r << 16) | ($g << 8) | $b;
    }

    protected function getCol(GdImage $image, float|int $x, float|int $y, int $background): int
    {
        $L = imagesx($image);
        $H = imagesy($image);
        if ($x < 0 || $x >= $L || $y < 0 || $y >= $H) {
            return $background;
        }

        return imagecolorat($image, (int)$x, (int)$y) ?: 0;
    }

    /**
     * @return array{int, int, int}
     */
    protected function getRGB(int $col): array
    {
        return [
            ($col >> 16) & 0xff,
            ($col >> 8) & 0xff,
            $col & 0xff,
        ];
    }

    /**
     * Validate the background image path. Return the image type if valid
     * @throws Exception
     */
    protected function validateBackgroundImage(string $backgroundImage): ?string
    {
        // check if file exists
        if (!file_exists($backgroundImage)) {
            $backgroundImageExploded = explode('/', $backgroundImage);
            $imageFileName = count($backgroundImageExploded) > 1
                ? $backgroundImageExploded[count($backgroundImageExploded) - 1]
                : $backgroundImage;

            throw new Exception('Invalid background image: ' . $imageFileName);
        }

        // check image type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            throw new Exception('Failed finfo_open');
        }

        $imageType = finfo_file($finfo, $backgroundImage);
        finfo_close($finfo);

        if (!in_array($imageType, $this->allowedBackgroundImageTypes)) {
            throw new Exception(
                'Invalid background image type! Allowed types are: '
                    . join(', ', $this->allowedBackgroundImageTypes)
            );
        }

        return $imageType ?: null;
    }

    /**
     * Create background image from type
     * @throws Exception
     */
    protected function createBackgroundImageFromType(string $backgroundImage, ?string $imageType = null): GdImage
    {
        $image = match ($imageType) {
            'image/jpeg' => imagecreatefromjpeg($backgroundImage),
            'image/png' => imagecreatefrompng($backgroundImage),
            'image/gif' => imagecreatefromgif($backgroundImage),
            default => throw new Exception('Not supported file type for background image!'),
        };

        if ($image === false) {
            throw new LogicException('Failed to create background image!');
        }

        return $image;
    }
}
