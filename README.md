# PHP client for [Joystick](https://www.getjoystick.com/)

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

$getContentsResponse = $client->getContents(['content-id1', 'content-id2']);

$getContentsResponse->myProperty1
$getContentsResponse->myProperty2
```

### Requesting Content by single Content Id

```php
$getContentResponse = $client->getContent('content-id1');
$getContentResponse->myProperty1
```

### Specifying additional parameters:

When creating the `ClientConfig` object, you can specify additional parameters which will be used
by all API calls from the client, for more details see
[API documentation](https://docs.getjoystick.com/api-reference/):

```php
$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'))
    ->setCacheExpirationSeconds(600) // 10 mins
    ->setSerialized(true)
    ->setParams([
        'param1' => 'value1',
        'param2' => 'value2',
     ])
     ->setSemVer('0.0.1')
     ->setUserId('user-id-1');
```

### Options

#### `fullResponse`

In most of the cases you will be not interested in the full response from the API, but if you're you can specify 
`fullResponse` option to the client methods. The client will return you raw API response:
```php
$getContentResponse = $client->getContent('content-id1', ['fullResponse' => true]);
// OR
$getContentsResponse = $client->getContents(['content-id1', 'content-id2'], ['fullResponse' => true]);
```

#### `serialized`

When `true`, we will pass query parameter `responseType=serialized` 
to [Joystick API](https://docs.getjoystick.com/api-reference-combine/). 

```php
$getContentResponse = $client->getContent('content-id1', ['serialized' => true]);
// OR
$getContentsResponse = $client->getContents(['content-id1', 'content-id2'], ['serialized' => true]);
```

#### `refresh`

If you want to ignore existing cache and request the new config – pass this option as `true`.

```php
$getContentResponse = $client->getContent('content-id1', ['refresh' => true]);
// OR
$getContentsResponse = $client->getContents(['content-id1', 'content-id2'], ['refresh' => true]);
```

This option can be set for every API call from the client by setting `setSerialized(true)`:

```php
$config = \Joystick\ClientConfig::create()
    ->setApiKey(getenv('JOYSTICK_API_KEY'))
    ->setSerialized(true)
```

### Caching 

By default, the client uses [array caching](https://packagist.org/packages/cache/array-adapter),
which means that if you build the HTTP application where each process exits after the request 
has been processed – the cache will be erased after the process is finished.

You can specify your [cache implementation which conforms PSR-16](https://packagist.org/providers/psr/simple-cache-implementation).


See [`examples/file-cache`](./examples/file-cache) for more details.

#### Clear the cache

If you want to clear the cache – run `$client->clearCache()`.

> Note that we will call `clear()` on the PSR-16 interface.
> Make sure that you use different cache instances in different places of your app


### HTTP Client

If you want to provide custom HTTP client, which may be useful for use-cases like specifying custom proxy,
collecting detailed metrics about HTTP requests,

You can specify your [HTTP client implementation which conforms PSR-18](https://packagist.org/providers/psr/simple-cache-implementation).

See [`examples/custom-http-client`](./examples/custom-http-client) for more details.

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
