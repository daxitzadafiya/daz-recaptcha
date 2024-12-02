Re CAPTCHA reCAPTCHA
==========

## Installation

```
composer require daz/no-captcha
```

## Laravel 5 and above

### Setup

**_NOTE_** This package supports the auto-discovery feature of Laravel 5.5 and above, So skip these `Setup` instructions if you're using Laravel 5.5 and above.

In `app/config/app.php` add the following :

1- The ServiceProvider to the providers array :

```php
daz\ReCaptcha\ReCaptchaServiceProvider::class,
```

2- The class alias to the aliases array :

```php
'ReCaptcha' => daz\ReCaptcha\Facades\ReCaptcha::class,
```

3- Publish the config file

```ssh
php artisan vendor:publish --provider="daz\ReCaptcha\ReCaptchaServiceProvider"
```

### Configuration

Add `ReCaptcha_SECRET` and `ReCaptcha_SITEKEY` in **.env** file :

```
ReCaptcha_SECRET=secret-key
ReCaptcha_SITEKEY=site-key
```

(You can obtain them from [here](https://www.google.com/recaptcha/admin))

### Usage

#### Init js source

With default options :

```php
 {!! ReCaptcha::renderJs() !!}
```

With [language support](https://developers.google.com/recaptcha/docs/language) or [onloadCallback](https://developers.google.com/recaptcha/docs/display#explicit_render) option :

```php
 {!! ReCaptcha::renderJs('fr', true, 'recaptchaCallback') !!}
```

#### Display reCAPTCHA

Default widget :

```php
{!! ReCaptcha::display() !!}
```

With [custom attributes](https://developers.google.com/recaptcha/docs/display#render_param) (theme, size, callback ...) :

```php
{!! ReCaptcha::display(['data-theme' => 'dark']) !!}
```

Invisible reCAPTCHA using a [submit button](https://developers.google.com/recaptcha/docs/invisible):

```php
{!! ReCaptcha::displaySubmit('my-form-id', 'submit now!', ['data-theme' => 'dark']) !!}
```
Notice that the id of the form is required in this method to let the autogenerated 
callback submit the form on a successful captcha verification.

#### Validation

Add `'g-recaptcha-response' => 'required|captcha'` to rules array :

```php
$validate = Validator::make(Input::all(), [
	'g-recaptcha-response' => 'required|captcha'
]);

```

##### Custom Validation Message

Add the following values to the `custom` array in the `validation` language file :

```php
'custom' => [
    'g-recaptcha-response' => [
        'required' => 'Please verify that you are not a robot.',
        'captcha' => 'Captcha error! try again later or contact site admin.',
    ],
],
```

Then check for captcha errors in the `Form` :

```php
@if ($errors->has('g-recaptcha-response'))
    <span class="help-block">
        <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
    </span>
@endif
```

### Testing

When using the [Laravel Testing functionality](http://laravel.com/docs/5.5/testing), you will need to mock out the response for the captcha form element.

So for any form tests involving the captcha, you can do this by mocking the facade behavior:

```php
// prevent validation error on captcha
ReCaptcha::shouldReceive('verifyResponse')
    ->once()
    ->andReturn(true);

// provide hidden input for your 'required' validation
ReCaptcha::shouldReceive('display')
    ->zeroOrMoreTimes()
    ->andReturn('<input type="hidden" name="g-recaptcha-response" value="1" />');
```

You can then test the remainder of your form as normal.

When using HTTP tests you can add the `g-recaptcha-response` to the request body for the 'required' validation:

```php
// prevent validation error on captcha
ReCaptcha::shouldReceive('verifyResponse')
    ->once()
    ->andReturn(true);

// POST request, with request body including g-recaptcha-response
$response = $this->json('POST', '/register', [
    'g-recaptcha-response' => '1',
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => '123456',
    'password_confirmation' => '123456',
]);
```

## Without Laravel

Checkout example below:

```php
<?php

require_once "vendor/autoload.php";

$secret  = 'CAPTCHA-SECRET';
$sitekey = 'CAPTCHA-SITEKEY';
$captcha = new \daz\ReCaptcha\ReCaptcha($secret, $sitekey);

if (! empty($_POST)) {
    var_dump($captcha->verifyResponse($_POST['g-recaptcha-response']));
    exit();
}

?>

<form action="?" method="POST">
    <?php echo $captcha->display(); ?>
    <button type="submit">Submit</button>
</form>

<?php echo $captcha->renderJs(); ?>
```

## Contribute

https://github.com/daz/no-captcha/pulls
