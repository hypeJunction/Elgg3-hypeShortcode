hypeShortcode
=============

![Elgg 7.x](https://img.shields.io/badge/Elgg-7.x-orange.svg?style=flat-square)

Add support for custom BB-style shortcodes

## Usage

### Register shortcode

```php
elgg()->shortcodes->register('mycode');

// then add a view in shortcodes/mycode
// view vars will contain attributes of the shortcode
``` 

### Generate a shortcode tag

```php
elgg()->shortcodes->generate('mycode', [
	'foo' => 'bar',
]);
```

### Expand shortcodes

```php
elgg()->shortcodes->expand($text);
```

### Strip shortcodes

```php
elgg()->shortcodes->strip($text);
```

## Compatibility

| Plugin version | Elgg version |
|---|---|
| 7.0.0 | 7.x |
| 6.0.0 | 6.x |
| 5.0.0 | 5.x |
| 4.0.0 | 4.x |
