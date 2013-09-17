# LaunchKey PHP SDK

## Description

Use to easily interact with LaunchKey's API.

## Installation

    $ git clone https://github.com/LaunchKey/launchkey-php.git

and

    $ php composer.phar install

## Usage

### To create a LaunchKey API object

    include("LaunchKey.php");
    $app_key = 1234567890; //log in to https://dashboard.launchkey.com to get keys
    $secret_key = "SECRET_KEY";
    $private_key = file_get_contents("/path/to/private.key");
    $domain = "yourdomain.tld";
    $launchkey = new LaunchKey($app_key, $secret_key, $private_key, $domain);


### When a user wishes to login

    $session = True;
    #Set session to False if it's a transactional authorization and a session doesn't need to be kept.
    $auth_request = $launchkey->authorize($username, $session);


### To check up on whether that user has launched or not

    $launch_status = $launchkey->poll_request($auth_request);


### To figure out whether the user authorized or denied the request

    if ($launchkey->is_authorized($launch_status['auth'], $auth_request))
        #Log the user in


### When a user logs out

    $launchkey->logout($auth_request);

### Handling a deorbit callback (https://launchkey.com/docs/api/authentication-flow/php#deorbit-callback)

    $launchkey->deorbit($deorbit, $signature);

More Documentation: https://launchkey.com/docs/api/authentication-flow/php#user-authentication

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request
