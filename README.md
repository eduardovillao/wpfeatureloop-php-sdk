# WPFeatureLoop PHP SDK

Collect feature requests and feedback from your WordPress plugin users.

## Installation

```bash
composer require eduardovillao/wpfeatureloop-sdk
```

## Usage

### Step 1: Initialize in your main plugin file

This must run on **every WordPress request** so REST API routes are registered:

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 */

require_once __DIR__ . '/vendor/autoload.php';
\WPFeatureLoop\Client::init('pk_live_your_public_key', 'your_project_id', [
    'language' => 'en',
]);

```

### Step 2: Render the widget

Add the widget wherever you want to display it:

```php
echo \WPFeatureLoop\Client::getInstance('your_project_id')->renderWidget();
```

## Configuration

| Option       | Type   | Default  | Description                                            |
| ------------ | ------ | -------- | ------------------------------------------------------ |
| `language`   | string | `'en'`   | Widget language                                        |
| `capability` | string | `'read'` | WordPress capability required to interact              |
| `metadata`   | array  | `[]`     | Extra data about the user, sent only with user consent |

## User Metadata

Use `metadata` to send extra context about the user along with their feedback —
their plan, their plugin version, whatever helps you read the request:

```php
\WPFeatureLoop\Client::init('pk_live_your_public_key', 'your_project_id', [
    'metadata' => [
        'plan'           => 'pro',
        'plugin_version' => '2.1.0',
    ],
]);
```

**Metadata is only sent for users who gave consent.** It follows the same rule as
the user's name and email: configuring it does not mean it gets collected. For a
user who has not opted in, the metadata is dropped before the request leaves the
site — so you can configure it freely and let each user decide.

Consent is stored per user in WordPress user meta and read on every request:

```php
$client = \WPFeatureLoop\Client::getInstance('your_project_id');

$client->getUserConsentStatus();  // true, false, or null when undecided
$client->setUserConsent(true);    // opt the current user in
```

## Supported Languages

| Code    | Language             | Status      |
| ------- | -------------------- | ----------- |
| `en`    | English              | Available   |
| `pt-BR` | Brazilian Portuguese | Available   |
| `es`    | Spanish              | Coming soon |

## Requirements

- PHP 7.4+
- WordPress 5.0+

## License

MIT
