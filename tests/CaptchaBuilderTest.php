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

        $this->assertTrue(file_exists(__DIR__.'/../out.jpg'));
    }

    public function testFingerPrint()
    {
        $int = count(CaptchaBuilder::create()
            ->build()
            ->getFingerprint()
        );

        $this->assertTrue(is_int($int));
    }
}