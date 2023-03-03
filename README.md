# Very short description of the package

[![GitHub Actions](https://github.com/getjoystick/joystick-php/actions/workflows/main.yml/badge.svg)](<(https://github.com/getjoystick/joystick-php/actions?query=branch%3Amaster)>)

[![Latest Stable Version](https://poser.pugx.org/getjoystick/joystick-php/v/stable.svg)](https://packagist.org/packages/getjoystick/joystick-php)
[![Total Downloads](https://poser.pugx.org/getjoystick/joystick-php/downloads.svg)](https://packagist.org/packages/getjoystick/joystick-php)
[![License](https://poser.pugx.org/getjoystick/joystick-php/license.svg)](https://packagist.org/packages/getjoystick/joystick-php)

This is the library that simplifies the way how you can communicate with [Joystick API](https://docs.getjoystick.com/).

## Requirements

PHP 7.2 and later

## Installation

You can install the package via [Composer](http://getcomposer.org/):

```bash
composer require getjoystick/joystick-php
```

We will try to find the PSR-18 compatible HTTP client within your dependencies using
[`php-http/discovery`](https://docs.php-http.org/en/latest/discovery.html), if you don't have one
installed, just run this command to install
[Guzzle HTTP client](https://docs.guzzlephp.org/en/stable/):

```bash
composer require guzzlehttp/guzzle
```

## Usage

To use the client, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once 'vendor/autoload.php';
```

Simple usage looks like this:

```php
$config = \Joystick\ClientConfig::create()->setApiKey(getenv('JOYSTICK_API_KEY'));

$client = \Joystick\Client::create($config);

$getContentsResponse = $client->getContents(['content-id1', 'content-id2'], [
    'fullResponse' => true,
]);
```

### Specifying additional parameters:

When creating the `ClientConfig` object, you can specify additional parameters which will be used
by all API calls from the client, for more details see 
[API documentation](https://docs.getjoystick.com/api-reference/):

```php
$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'))
    ->setCacheExpirationSeconds(600) // 10 mins
    ->setParams([
        'param1' => 'value1',
        'param2' => 'value2',
     ])
     ->setSemVer('0.0.1')
     ->setUserId('user-id-1');
```

### Caching 

By default, the client uses [array caching](https://packagist.org/packages/cache/array-adapter),
which means that if you build the HTTP application where each process exists after the request 
has been processed – the cache will be erased after the process is finished.

You can specify your own cache implementation which conforms PSR

## Testing

To run unit tests, just run:
```bash
phpunit
```

### Security

If you discover any security related issues, please email [letsgo@getjoystick.com](letsgo@getjoystick.com) 
instead of using the issue tracker.

## Credits

- [Joystick](https://github.com/getjoystick)
- [All Contributors](../../contributors)

## License

The MIT. Please see [License File](LICENSE.md) for more information.
