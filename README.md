# HTML2JSONLD-PHP

This library offers the ability to read HTML from URLs, file paths and strings and return any Markup (JSON-LD or Microdata) found within it in the JSON-LD format.

## Installation
⚠️ HTML2JSONLD is currently in beta status.

Install this library using composer:

```
composer install audriga/html2jsonld-php --prefer-dist --no-dev
```

## Requirements

This library requires PHP 8.1 or greater.

## Usage

To use this library, import it in your PHP file using

```php
use SML\Html2JsonLd\Util\MarkupUtil;
```

and extract Markup data from an HTML source through one of the following methods.

These methods will return strings which contain a JSON representation of any markup found in the source. To turn it into an Object or Map, use [PHP's json_decode() method](https://www.php.net/manual/de/function.json-decode.php).

### getJsonLdFromHtmlString

Extracts any markup data from the given `$html` String. `$url` is needed to resolve relative file paths found in the markup. The implementation does not need to connect to this URL.

Example usage:

```php
$html = "<!DOCTYPE html>\n<html> [...] </html>"
$result = MarkupUtil::getJsonLdFromHtmlString($html, "http://example.com")
```

### getJsonLdFromFile

Extracts any markup data from the file located under `$source`. `$url` is needed to resolve relative file paths found in the markup. The implementation does not need to connect to this URL.

Example usage:

```php
$result = MarkupUtil::getJsonLdFromFile("foo/bar.html", "http://example.com")
```

### getJsonLdFromUrl

Tries to retrieve HTML form `$source` using the built-in PHP cURL client and extract any markup data within it.

Example usage:

```php
$result = MarkupUtil::getJsonLdFromUrl("http://example.com")
```

### Image Conversion 

Your Markdown content might contain image data. This library will call a URL available in it to convert them to Base64 binary Data-URLs.

## Development
### Installation

After cloning this repository, run `composer install` to install dependencies (including dev-dependencies).

### Tests

This project uses PHPUnit version 10 or greater.

To run every test:  `vendor/bin/phpunit tests/unit` from within the project root.

To add a new test file, simply put it under `tests/resources` as `<your-test-file>.html`.

#### Tested against 
PHP version 8.1