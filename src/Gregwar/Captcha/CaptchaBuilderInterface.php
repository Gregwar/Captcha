<?php

namespace Gregwar\Captcha;

/**
 * A Captcha builder
 */
interface CaptchaBuilderInterface
{
    /**
     * Builds the code
     * @param int[] $fingerprint
     */
    public function build(int $width, int $height, ?string $font, ?array $fingerprint): static;

    /**
     * Saves the code to a file
     */
    public function save(string $filename, int $quality): void;

    /**
     * Gets the image contents
     */
    public function get(int $quality): string;

    /**
     * Outputs the image
     */
    public function output(int $quality): void;
}
