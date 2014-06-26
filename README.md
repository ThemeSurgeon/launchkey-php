# LaunchKey PHP Client [![Build Status](https://travis-ci.org/LaunchKey/launchkey-php.png?branch=master)](https://travis-ci.org/LaunchKey/launchkey-php)

Use to easily interact with LaunchKey's API.

## Installation

The recommended installation is through [Composer](http://getcomposer.org/).
Install Composer:

    $ curl -sS https://getcomposer.org/installer | php

And then add LaunchKey as a dependency:

    $ php composer.phar require launchkey/launchkey:0.1.0

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

## Configuration

Configuration can either be done globally:

```php
use LaunchKey\LaunchKey;

LaunchKey::configure(array(
    'domain'     => 'yourdomain.tld',
    'app_key'    => 1234567890,
    'secret_key' => 'abcdefghijklmnopqrstuvwxyz',
    'keypair'    => file_get_contents('path/to/private_key.pem'),
));
```

Or by instantiating a new `LaunchKey\Client`:

```php
$launchkey = new LaunchKey\Client(array(
    'domain'     => 'yourdomain.tld',
    'app_key'    => 1234567890,
    'secret_key' => 'abcdefghijklmnopqrstuvwxyz',
    'keypair'    => file_get_contents('path/to/private_key.pem'),
));
```

## Usage

### Authorization

Make an authorization request with the user's LaunchKey username:

```php
$auth_request = LaunchKey::authorize($username);
// => "71xmyusohv0171fg..."
```

For a transactional authorization request, pass the `$session` option as `FALSE`:

```php
LaunchKey::authorize($username, FALSE);
```

To have a user_push_id returned, pass the `$user_push_id` option as `TRUE`:

```php
LaunchKey::authorize($username, FALSE, TRUE);
```

### Polling

Check whether the user has responded to the authorization request:

```php
// The user has not responded:
$launch_status = LaunchKey::poll($auth_request);
// =>  FALSE

// The user has responded:
$launch_status = LaunchKey::poll($auth_request);
// => array('auth' => '...', 'user_hash' => '...')
```

### Validation

To test whether the user accepted or rejected the request:

```php
if (LaunchKey::is_authorized($launch_status['auth'], $auth_request))
{
    // The user accepted the request
}
else
{
    // The user rejected the request
}
```

### Log Out

To end a session:

```php
LaunchKey::deauthorize($auth_request);
```

Alternatively, a deorbit callback can be handled (see [deorbit docs](https://launchkey.com/docs/api/authentication-flow/php#deorbit-callback) for details):

```php
$params = array(
    'deorbit'   => $_GET['deorbit'],
    'signature' => $_GET['signature'],
);

if ($user_hash = LaunchKey::deorbit($params))
{
    // The deorbit was successful
}
else
{
    // The deorbit was invalid or expired
}
```

More Documentation: https://launchkey.com/docs/api/authentication-flow/php#user-authentication

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request
