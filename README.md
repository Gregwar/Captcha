Captcha
=======

![Captchas examples](http://gregwar.com/captchas.png)

Installation
============

With composer :

``` json
{
    ...
    "require": {
        "gregwar/captcha": "1.*"
    }
}
```

Usage
=====

You can create a captcha with the `CaptchaBuilder` :

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

Or inline it directly in the HTML page:

```php
<img src="<?php echo $builder->inline(); ?>" />
```

You'll be able to get the code and compare it with a user input :

```php
<?php

// Example: storing the phrase in the session to test for the user 
// input later
$_SESSION['phrase'] = $builder->getPhrase();
```

You can compare the phrase with user input:
```php
if($builder->testPhrase($userInput)) {
    // instructions if user phrase is good
}
else {
    // user phrase is wrong
}
```
You can compare user session phrase with user input:
```php
if($builder->testPhrase($userInput, $_SESSION['phrase'])) {
    // instructions if user phrase is good
}
else {
    // user phrase is wrong
}
```

API
===

You can use theses functions :

* **__construct($phrase = null)**, constructs the builder with the given phrase, if the phrase is null, a random one will be generated
* **getPhrase()**, allow you to get the phrase contents
* **setDistortion($distortion)**, enable or disable the distortion, call it before `build()`
* **isOCRReadable()**, returns `true` if the OCR can be read using the `ocrad` software, you'll need to have shell_exec enabled, imagemagick and ocrad installed
* **buildAgainstOCR($width = 150, $height = 40, $font = null)**, builds a code until it is not readable by `ocrad`
* **build($width = 150, $height = 40, $font = null)**, builds a code with the given $width, $height and $font. By default, a random font will be used from the library
* **save($filename, $quality = 80)**, saves the captcha into a jpeg in the $filename, with the given quality
* **get($quality = 80)**, returns the jpeg data
* **output($quality = 80)**, directly outputs the jpeg code to a browser
* **setBackgroundColor($r, $g, $b)**, sets the background color to force it (this will disable many effects and is not recommended)
* **setBackgroundImages(array($imagepath1, $imagePath2))**, Sets custom background images to be used as captcha background. It is recommended to disable image effects when passing custom images for background (ignore_all_effects). A random image is selected from the list passed, the full paths to the image files must be passed.
* **setInterpolation($interpolate)**, enable or disable the interpolation (enabled by default), disabling it will be quicker but the images will look uglier
* **setIgnoreAllEffects($ignoreAllEffects)**, disable all effects on the captcha image. Recommended to use when passing custom background images for the captcha.
* **testPhrase($phrase)**, returns true if the given phrase is good
* **setMaxBehindLines($lines)**, sets the maximum number of lines behind the code
* **setMaxFrontLines($lines)**, sets the maximum number of lines on the front of the code

Symfony 2 Bundle
================

You can have a look at the following repository to enjoy the Symfony 2 bundle packaging this captcha generator :
https://github.com/Gregwar/CaptchaBundle

License
=======

This library is under MIT license, have a look to the `LICENSE` file
