hypeShortcode
=============

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
