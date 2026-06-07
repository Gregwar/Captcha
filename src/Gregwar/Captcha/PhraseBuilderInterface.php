<?php declare(strict_types=1);

namespace Gregwar\Captcha;

/**
 * Interface for the PhraseBuilder
 *
 * @author Gregwar <g.passault@gmail.com>
 */
interface PhraseBuilderInterface
{
    /**
     * Generates a random phrase of given length with given charset
     */
    public function build(?int $length = null, ?string $charset = null): string;

    /**
     * "Niceize" a code
     */
    public function niceize(string $str): string;
}
