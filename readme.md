# Telegram Bot API Client

[Telegram](https://telegram.org) [Bot](https://core.telegram.org/bots) [API](https://core.telegram.org/bots/api) Client

## Maintenance branch requirements

This fork's maintenance branch targets PHP 8.2+ and Guzzle 7.10+. This is a
compatibility change from the historical PHP/Guzzle 6 version. No new Packagist
release has been published: the commands below refer to the original upstream
package, not these unreleased changes. To test this checkout, run `composer install`
and `composer test`.

The generic `Api::request()` can call newer Bot API methods; the historical typed
wrappers are not a complete model of the current Telegram API. TLS verification
stays enabled and redirects are rejected. Arrays and objects are encoded as JSON,
booleans as `true`/`false`, null fields are omitted, and upload streams are preserved.
Malformed responses raise `UnexpectedValueException`; valid API errors continue
through `Response` / `NotOkException`. Network and server exceptions propagate.

## Installation

### Composer

The preferred way to install this extension is through [Composer](https://getcomposer.org/).

Either run

```
php composer.phar require "zelenin/telegram-bot-api" "~0.0"
```

or add

```
"zelenin/telegram-bot-api": "~0.0"
```

to the require section of your ```composer.json```

## Usage

```php
$client = new Zelenin\Telegram\Bot\Api($token);

try {
    $response = $client->sendMessage([
        'chat_id' => $chatId,
        'text' => 'Test message'
    ]);
    print_r($response);
    
    $response = $client->sendPhoto([
    	'chat_id' => $myId,
    	'photo' => fopen('/home/www/photo.jpg', 'r')
    ]);
    print_r($response);
} catch (\Zelenin\Telegram\Bot\NotOkException $e) {
    echo $e->getMessage();
}
```

See [Bot API documentation](https://core.telegram.org/bots/api) for other methods.

## Author

[Aleksandr Zelenin](https://github.com/zelenin/), e-mail: [aleksandr@zelenin.me](mailto:aleksandr@zelenin.me)
