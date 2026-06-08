<?php

declare(strict_types=1);

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
    public function build(int $width = 150, int $height = 40, ?string $font = null, ?array $fingerprint = null): static;

    /**
     * Saves the code to a file
     */
    public function save(?string $filename = null, int $quality = 90): void;

    /**
     * Gets the image contents
     */
    public function get(int $quality = 90): string;

    /**
     * Outputs the image
     */
    public function output(int $quality = 90): void;
}
