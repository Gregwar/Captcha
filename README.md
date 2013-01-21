Captcha
=======

![Captchas examples](http://gregwar.com/captchas.png)


Installation
============

With composer :

```
{
    ...
    "require": {
        "gregwar/captcha": "dev-master"
    }
}
```

Usage
=====

You can create a captcha with the `CapthcaBuilder` :

```php
<?php

use Gregwar\Captcha\CaptchaBuilder;

$builder = new CaptchaBuilder;
$builder->build();
```

You can then save it to a file :

```php
<?php

$builder->save('out.jpg');
```

Or output it directly :

```php
<?php

header('Content-type: image/jpeg');
$builder->output();
```

You'll be able to get the code and compare it with a user input :

```php
<?php

// Example: storing the phrase in the session to test for the user 
// input later
$_SESSION['phrase'] = $builder->getPhrase();
```

API
===

You can use theses functions :

* **__construct($phrase = null)**, constructs the builder with the given phrase, if the phrase is null, a random one will be generated
* **getPhrase()**, allow you to get the phrase contents
* **setDistortion($distortion)**, enable or disable the distortion, call it before `build()`
* **isOCRReadable()**, returns `true` if the OCR can be read using the `ocrad` software, you'll need to have shell_exec enabled, imagemagick and ocrad installed
* **buildAgainstOCR()**, builds a code until it is not readable by `ocrad`
* **build($width = 150, $height = 40, $font = null)**, builds a code with the given $width, $height and $font. By default, a random font will be used from the library
* **save($filename, $quality = 80)**, saves the captcha into a jpeg in the $filename, with the given quality
* **get($quality = 80)**, returns the jpeg data
* **output($quality = 80)**, directly outputs the jpeg code to a browser

Symfony 2 Bundle
================

You can have a look at the following repository to enjoy the Symfony 2 bundle packaging this captcha generator :
https://github.com/Gregwar/CaptchaBundle

License
=======

This library is under MIT license, have a look to the `LICENSE` file
