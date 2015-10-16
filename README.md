# Eventful Web Api
A PHP implementation of [Eventful's](http://api.eventful.com/) Web API.

### It includes the following
- Helper methods for all API methods
- PSR-4 autoloading support.

### Requirements
* PHP 5.4.1 or greater
* [themattharris/tmhoauth](https://packagist.org/packages/themattharris/tmhoauth)
* [fpoirotte/http_request2](https://packagist.org/packages/fpoirotte/http_request2)

### Installation
Add `eventful-web-api` as a dependency to your composer.json:
```
"require": {
    "realdark/eventful-web-api": "dev-master"
}
```
All dependencies will be installed automatically.

### Original version
You can download the original version from [here](http://api.eventful.com/libs/php/Services_Eventful).

### Modifications
* All http protocols are updated to https
* Pear is removed. All errors are now Exceptions
* Modified to work with composer

### Examples
You can see them here: [Eventful Api Doc](http://api.eventful.com/docs)
```
$key = 'your api key';

$api = new Services_Eventful($key);

$args = [
     'keywords' => 'eminem'
];

$events = $api->call('events/search', $args);

var_dump($events);
```
> All responses are [SimpleXMLElement](http://php.net/manual/en/book.simplexml.php) objects

# License
Eventful, Inc license. Please see [LICENSE.md](https://github.com/realdark/eventful-web-api/blob/master/LICENSE) for more information.
