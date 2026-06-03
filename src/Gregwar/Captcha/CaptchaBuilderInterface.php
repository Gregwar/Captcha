<?php

namespace Gregwar\Captcha;

/**
 * A Captcha builder
 */
interface CaptchaBuilderInterface
{
    /**
     * Builds the code
     */
    public function build(int $width, int $height, ?string $font, $fingerprint);

    /**
     * Saves the code to a file
     */
    public function save(string $filename, int $quality): void;

    /**
     * Gets the image contents
     */
    public function get(int $quality);

    /**
     * Outputs the image
     */
    public function output(int $quality);
}
