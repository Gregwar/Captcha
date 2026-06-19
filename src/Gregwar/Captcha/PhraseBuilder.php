<?php

declare(strict_types=1);

namespace Gregwar\Captcha;

use Gregwar\Captcha\Exception\InvalidArgumentException;

/**
 * Generates random phrase
 *
 * @author Gregwar <g.passault@gmail.com>
 */
class PhraseBuilder implements PhraseBuilderInterface
{
    public function __construct(
        public int $length = 5,
        public string $charset = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    ) {
        //
    }

    /**
     * Generates a random phrase of given length with given charset
     */
    public function build(?int $length = null, ?string $charset = null): string
    {
        if ($length !== null) {
            $this->length = $length;
        }
        if ($charset !== null) {
            $this->charset = $charset;
        }

        $phrase = '';

        for ($i = 0; $i < $this->length; $i++) {
            $phrase .= $this->getRandomCharacter();
        }

        return $phrase;
    }

    /**
     * "Niceize" a code
     */
    public function niceize(string $str): string
    {
        return self::doNiceize($str);
    }

    /**
     * A static helper to niceize
     */
    public static function doNiceize(string $str): string
    {
        return strtr(strtolower($str), '01', 'ol');
    }

    /**
     * A static helper to compare
     */
    public static function comparePhrases(string $str1, string $str2): bool
    {
        return self::doNiceize($str1) === self::doNiceize($str2);
    }

    private function getRandomCharacter(): string
    {
        if ($this->charset === '') {
            throw new InvalidArgumentException('Charset cannot be empty.');
        }

        try {
            return $this->charset[random_int(0, strlen($this->charset) - 1)];
        } catch (\Random\RandomException $e) {
            $chars = str_split($this->charset);

            return $chars[array_rand($chars)];
        }
    }
}
