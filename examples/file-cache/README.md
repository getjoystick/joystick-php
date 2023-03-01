# File cache

This is an example which uses `composer` to install the library and shows 
how Joystick PHP client may flexibly you can configure your own cache provider.


## How to run?

1.   Change working directory to this folder
2.   `composer install`
3.   `JOYSTICK_API_KEY=<api-key> php ./main.php`
4.   It will call `getContents` multiple times with response time
5.   Run command from 3rd paragraph again to see that for the next 10 seconds
     response time will tend to zero.
