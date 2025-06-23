<?php

namespace Test;

use Gregwar\Captcha\CaptchaBuilder;
use PHPUnit\Framework\TestCase;

class CaptchaBuilderTest extends TestCase
{
    /**
     * Captcha phrases
     *
     * @var array
     */
    private $phrases = array(
        '@!#*?',
        's3cr3t',
        'p4ssw0rd',
        'hello',
        'world'
    );

    public function testCreate()
    {
        $this->assertInstanceOf('Gregwar\Captcha\CaptchaBuilder', CaptchaBuilder::create());
    }

    public function testBuild()
    {
        $this->assertInstanceOf('Gregwar\Captcha\CaptchaBuilder', CaptchaBuilder::create()->build());

        foreach ($this->phrases as $phrase) {
            $builder = new CaptchaBuilder($phrase);
            $this->assertEquals($phrase, $builder->getPhrase());
        }
    }

    public function testDemo()
    {
        $captcha = new CaptchaBuilder();
        $captcha
            ->build()
            ->save('out.jpg')
        ;

        $this->assertTrue(file_exists(__DIR__ . '/../out.jpg'));
    }

    public function testFingerPrint()
    {
        $int = count(CaptchaBuilder::create()
            ->build()
            ->getFingerprint());

        $this->assertTrue(is_int($int)); // @phpstan-ignore function.alreadyNarrowedType
    }

    public function testImageType()
    {
        $types = array('jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'gif' => IMAGETYPE_GIF);
        foreach ($types as $type => $expected) {
            $captcha = new CaptchaBuilder();
            $captcha->setImageType($type)->build();

            // Test save()
            $captcha->save('out.' . $type);
            $this->assertType(__DIR__ . '/../out.' . $type, $expected);

            // Test output()
            ob_start();
            $captcha->output();
            file_put_contents(__DIR__ . '/../out.' . $type, ob_get_clean());
            $this->assertType(__DIR__ . '/../out.' . $type, $expected);
        }
    }

    public function testImageTransparency()
    {
        foreach (array(0 => false, 127 => true) as $alpha => $expected) {
            $captcha = new CaptchaBuilder();
            $captcha->setImageType('png')
                ->setBackgroundColor(0, 0, 0)
                ->setBackgroundAlpha($alpha)
                ->build()
                ->save($filename = __DIR__ . '/out.png');

            $this->assertTransparency($filename, $expected);
        }
    }

    /**
     * @param string $filename
     * @param bool $expected
     * @return void
     */
    private function assertTransparency($filename, $expected)
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
        imagedestroy($image);

        $this->assertSame($expected, $hasTransparency, 'The PNG does not have any transparent pixels.');
    }

    /**
     * @param string $file
     * @param int $expected IMAGETYPE_JPEG / IMAGETYPE_PNG / IMAGETYPE_GIF
     * @return void
     */
    private function assertType($file, $expected)
    {
        $info = getimagesize($file);
        if ($info === false) {
            $this->fail("Not a valid image.");
        } else {
            $this->assertSame($expected, $info[2], 'Unexpected image type.');
        }
    }
}
