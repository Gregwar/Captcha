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

    public function testCreate(): void
    {
        $this->assertInstanceOf('Gregwar\Captcha\CaptchaBuilder', CaptchaBuilder::create());
    }

    public function testBuild(): void
    {
        $this->assertInstanceOf('Gregwar\Captcha\CaptchaBuilder', CaptchaBuilder::create()->build());

        foreach ($this->phrases as $phrase) {
            $builder = new CaptchaBuilder($phrase);
            $this->assertEquals($phrase, $builder->getPhrase());
        }
    }

    public function testDemo(): void
    {
        $captcha = new CaptchaBuilder();
        $captcha
            ->build()
            ->save($filename = __DIR__.'/../generated/out.jpg');

        $this->assertTrue(file_exists($filename));
    }

    public function testFingerPrint(): void
    {
        $int = count(CaptchaBuilder::create()
            ->build()
            ->getFingerprint());

        $this->assertTrue(is_int($int)); // @phpstan-ignore function.alreadyNarrowedType
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
            $captcha->save($filename = __DIR__.'/../generated/out.' . $type);
            $this->assertType($filename, $expected);

            // Test output()
            ob_start();
            $captcha->output();
            file_put_contents($filename, ob_get_clean());
            $this->assertType($filename, $expected);
        }
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
}
