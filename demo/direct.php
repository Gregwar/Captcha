<?php

include('../CaptchaBuilder.php');
include('../PhraseBuilder.php');

use Gregwar\Captcha\CaptchaBuilder;

$captcha = new CaptchaBuilder;
echo $captcha
    ->build()
    ->get(20)
    ;
