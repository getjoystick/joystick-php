# Symfony Http Client

This is an example which uses `composer` to install the library and shows 
how Joystick PHP client may flexibly:

-   use any implementation of PSR-18 (here – Symfony HTTP client)
-   find existing implementations in your project using 
    [`php-http/discovery`](https://docs.php-http.org/en/latest/discovery.html)
-   give you an ability to configure http requests, i.e. specify `timeout`, `proxy`
-   wrap existing implementation of PSR-18 client to gather measurements of separate http calls
    (log them, debug, measure time, etc.)

## How to run?

-   Change working directory to this folder
-   `composer install`
-   `JOYSTICK_API_KEY=<api-key> php ./main.php`

## Custom environment variables

You can provide custom environments variable along with `JOYSTICK_API_KEY` to test different 
behaviors:

| Environment variable | Possible values            | Description                                                                                                                                  |
|----------------------|----------------------------|----------------------------------------------------------------------------------------------------------------------------------------------|
| AUTODISCOVER         | `true` or any other value  | makes library to find the existing HTTP implemntation with    [ `php-http/discovery` ]( https://docs.php-http.org/en/latest/discovery.html ) |
| TIMEOUT              | any number (int, or float) | enables custom timeout (in seconds)                                                                                                          |
| MEASURE_PERFORMANCE  | `true` or any other value  | uses wrapping around Symfony HTTP Client to measure the performance of every http request                                                    |

