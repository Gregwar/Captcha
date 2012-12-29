<?php

include('../CaptchaBuilder.php');
include('../PhraseBuilder.php');

use Gregwar\Captcha\CaptchaBuilder;

/**
 * Generates 1000 captchas and try to read their code with the
 * ocrad OCR
 */

$tests = 1000;
$passed = 0;

shell_exec('rm passed*.jpg');

for ($i=0; $i<$tests; $i++) {
    echo "Captcha $i/$tests... ";

    $captcha = new CaptchaBuilder;

    $captcha
        ->build()
        ->save('out.jpg', 20)
        ;

    shell_exec('convert out.jpg out.pgm');
    $result = trim(shell_exec('ocrad out.pgm'));

    if ($result == $captcha->getPhrase()) {
        echo "passed at ocr\n";
        shell_exec("cp out.jpg passed$passed.jpg");
        $passed++;
    } else {
        echo "failed\n";
    }
}

echo "\n";
echo "Over, $passed/$tests readed with OCR\n";
