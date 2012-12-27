<?php

namespace Gregwar\Captcha;

/**
 * Generates random phrase
 *
 * @author Gregwar <g.passault@gmail.com>
 */
class PhraseBuilder
{
    /**
     * Generates  random phrase of given length with given charset
     */
    public static function build($length = 5, $charset = 'abcdefghijklmnopqrstuvwxyz0123456789')
    {
        $phrase = '';
        $chars = str_split($charset);

        for ($i = 0; $i < $length; $i++) {
            $phrase .= $chars[array_rand($chars)];
        }

        return $phrase;
    }
}
