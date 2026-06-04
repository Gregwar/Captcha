<?php

namespace Gregwar\Captcha;

/**
 * Generates random phrase
 *
 * @author Gregwar <g.passault@gmail.com>
 */
class PhraseBuilder implements PhraseBuilderInterface
{
    public int $length = 5;

    public string $charset = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Constructs a PhraseBuilder with given parameters
     */
    public function __construct(int $length = 5, string $charset = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $this->length = $length;
        $this->charset = $charset;
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
        $chars = str_split($this->charset);

        for ($i = 0; $i < $this->length; $i++) {
            $phrase .= $chars[array_rand($chars)];
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
}
