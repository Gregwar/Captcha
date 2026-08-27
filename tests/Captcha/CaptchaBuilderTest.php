<?php

namespace Captcha;

use Gregwar\Captcha\CaptchaBuilder;
use PHPUnit\Framework\TestCase;

class CaptchaBuilderTest extends TestCase
{
    /**
     * Captcha phrases
     *
     * @var string[]
     */
    private array $phrases = [
        '@!#*?',
        's3cr3t',
        'p4ssw0rd',
        'hello',
        'world'
    ];

    public function testBuild(): void
    {
        $this->assertInstanceOf('Gregwar\Captcha\CaptchaBuilder', CaptchaBuilder::create()->build());

        foreach ($this->phrases as $phrase) {
            $builder = new CaptchaBuilder($phrase);
            $this->assertEquals($phrase, $builder->getPhrase());
        }
    }

    public function testBuildWithOutOfRangeFingerprint(): void
    {
        $builder = new CaptchaBuilder();
        $builder->build(150, 40, null, [99999, -99999, PHP_INT_MAX, PHP_INT_MIN]);

        $this->assertInstanceOf('GdImage', $builder->getGd());
    }

    public function testCreate(): void
    {
        $this->assertInstanceOf('Gregwar\Captcha\CaptchaBuilder', CaptchaBuilder::create());
    }

    public function testDemo(): void
    {
        $captcha = new CaptchaBuilder();
        $captcha
            ->build()
            ->save($filename = __DIR__ . '/../generated/out.jpg');

        $this->assertTrue(file_exists($filename));
    }

    public function testFingerPrint(): void
    {
        $int = count(CaptchaBuilder::create()
            ->build()
            ->getFingerprint());

        $this->assertTrue(is_int($int)); // @phpstan-ignore function.alreadyNarrowedType
    }

    public function testImageTransparency(): void
    {
        foreach ([0 => false, 127 => true] as $alpha => $expected) {
            $captcha = new CaptchaBuilder();
            $captcha->setImageType('png')
                ->setBackgroundColor(0, 0, 0)
                ->setBackgroundAlpha($alpha)
                ->build()
                ->save($filename = __DIR__ . '/../generated/out.png');

            $this->assertTransparency($filename, $expected);
        }
    }

    public function testImageType(): void
    {
        $types = [
            'jpeg' => IMAGETYPE_JPEG,
            'png' => IMAGETYPE_PNG,
            'gif' => IMAGETYPE_GIF
        ];
        foreach ($types as $type => $expected) {
            $captcha = new CaptchaBuilder();
            $captcha->setImageType($type)->build();

            // Test save()
            $captcha->save($filename = __DIR__ . '/../generated/out.' . $type);
            $this->assertType($filename, $expected);

            // Test output()
            ob_start();
            $captcha->output();
            file_put_contents($filename, ob_get_clean());
            $this->assertType($filename, $expected);
        }
    }

    public function testRandClampsFingerprintValuesToRequestedRange(): void
    {
        $builder = new CaptchaBuilder();
        $this->setFingerprint($builder, [9999, -9999, 5]);

        $this->assertSame(8, $this->rand($builder, -8, 8));
        $this->assertSame(-8, $this->rand($builder, -8, 8));
        $this->assertSame(5, $this->rand($builder, -8, 8));
    }

    public function testRandColorStaysWithinColorRange(): void
    {
        $builder = new CaptchaBuilder();
        $this->setFingerprint($builder, [9999, -9999]);

        $randColor = new \ReflectionMethod(CaptchaBuilder::class, 'randColor');

        $this->assertSame(255, $randColor->invoke($builder, 0, 255));
        $this->assertSame(0, $randColor->invoke($builder, 0, 255));
    }

    public function testRandRespectsRequestedRange(): void
    {
        $builder = new CaptchaBuilder();

        mt_srand(42);
        $values = [];
        for ($i = 0; $i < 500; $i++) {
            $values[] = $this->rand($builder, -8, 8);
        }

        $this->assertGreaterThanOrEqual(-8, min($values));
        $this->assertLessThanOrEqual(8, max($values));
        $this->assertLessThan(0, min($values), 'rand() should produce negative values');
        $this->assertGreaterThan(0, max($values), 'rand() should produce positive values');

        $values = [];
        for ($i = 0; $i < 500; $i++) {
            $values[] = $this->rand($builder, 0, 1000);
        }

        $this->assertGreaterThan(255, max($values), 'rand() should produce values above 255');
    }

    private function assertTransparency(string $filename, bool $expected): void
    {
        $image = imagecreatefrompng($filename);
        if (!$image) {
            $this->fail('Could not open PNG file.');
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        $hasTransparency = false;

        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    $hasTransparency = true;
                    break 2; // Quit both loops
                }
            }
        }

        $this->assertSame($expected, $hasTransparency, 'The PNG does not have any transparent pixels.');
    }

    private function assertType(string $file, int $expected): void
    {
        $info = getimagesize($file);
        if ($info === false) {
            $this->fail("Not a valid image.");
        } else {
            $this->assertSame($expected, $info[2], 'Unexpected image type.');
        }
    }

    private function rand(CaptchaBuilder $builder, int $min, int $max): int
    {
        $rand = new \ReflectionMethod(CaptchaBuilder::class, 'rand');
        $value = $rand->invoke($builder, $min, $max);
        $this->assertIsInt($value);

        return $value;
    }

    /**
     * @param int[] $fingerprint
     */
    private function setFingerprint(CaptchaBuilder $builder, array $fingerprint): void
    {
        foreach (['fingerprint' => $fingerprint, 'useFingerprint' => true] as $name => $value) {
            $property = new \ReflectionProperty(CaptchaBuilder::class, $name);
            $property->setValue($builder, $value);
        }
    }
}
