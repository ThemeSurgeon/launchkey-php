# LaunchKey SDK for PHP

[![Latest Stable Version](https://poser.pugx.org/launchkey/launchkey/v/stable.svg)](https://packagist.org/packages/launchkey/launchkey)

[![Build Status](https://travis-ci.org/LaunchKey/launchkey-php.svg)](https://travis-ci.org/LaunchKey/launchkey-php)

  * [Overview](#overview)
  * [Pre-Requisites](#prerequisites)
  * [Installing](#installing)
  * [Usage](#usage)
  * [Support](#support)

<a name="overview"></a>
# Overview

LaunchKey is an identity and access management platform  This SDK enables developers to quickly integrate
the LaunchKey platform and PHP based applications without the need to directly interact with the platform API.

Developer documentation for using the LaunchKey API is found [here](https://launchkey.com/docs/).

An overview of the LaunchKey platform can be found [here](https://launchkey.com/platform).

#  <a name="prerequisites"></a>Pre-Requisites

Utilization of the LaunchKey SDK requires the following items:

 * LaunchKey Account - The [LaunchKey Mobile App](https://launchkey.com/app) is required to set up a new account and
 access the LaunchKey Dashboard.
 
 * An application - A new application can be created in the [LaunchKey Dashboard](https://dashboard.launchkey.com/).
   From the application, you will need the following items found in the keys section of the application details:

    * The app key
    * The secret key
    * The private key


<a name="installing"></a>
# Installing

The preferred way to install the LaunchKey SDK for PHP is to use Composer, the PHP package manager. Simply type
the following into a terminal window:

```bash
composer require launchkey/launchkey
```

<a name="usage"></a>
# Usage


## Instantiate an SDK Client

The easiest way to create a client is by passing an app key, secret key, and private key location to the factory:

    ```php
    $client = \LaunchKey\SDK\Client::factory(
        "1234567890",
        "supersecretandwayrandomsecretkey",
        file_get_contents("/usr/local/etc/launchkey-app-private-key.pem")
    );

    ```

If needed, you can have better control over the environment by using the Config object:

    ```php
    $config = new \LaunchKey\SDK\Config();
    $config->setAppKey("1234567890")
        ->setSecretKey("supersecretandwayrandomsecretkey")
        ->setPrivateKeyLocation("/usr/local/etc/launchkey-app-private-key.pem");

Using the config object allows you to set many other configuration items:

* Private Key Location - Specify the location of the private key file instead of loading it in yourself.

* Private key password - if your RSA private key is password protected

* Cache - The LaunchKey public key is cached to improve performance.  The default is local memory cache.  The config
allows you to specify a different ```LaunchKey\SDK\Cache\Cache``` implementation.

* Event Dispatcher - Events are dispatched by the SDK client.  The default
Event Dispatcher is a local synchronous dispatcher.

* Logger - Log debug and error messages with context utilizing a PSR compliant
logger.  By default no logger is implemented.

* API Base URL - If you are using a premise based LaunchKey Engine or are psarticipating in a special preview test, you
would specify the URL of the LaunchKey Engine API here. 

* API Request Timeout - How long the cURL client will wait for the remote API to respond before timing out.  The
default is 0 (infinite).

* API Connect Timeout - How long the cURL client will wait while connecting to the remote API before timing out.  The
default is 0 (infinite).

## Request a user authentication

Authentication is used to start a durable user session.  Application login would
be an example of when to use user authentication.

Authentication creates a state know as "Orbiting" in the LaunchKey system.

    ```php
    $authRequest = $client->auth()->authenticate("LaunchKeyUserName");

    ```

A ```\LaunchKey\SDK\Domain\AuthRequest``` object will be returned to identify
the authorization request created for this authentication request.  The auth
request ID is this object will be used to identify this in the LaunchKey Engine
form this point forward.

## Request a user to authorize

Authorization is used to authorize a single request.  It does not create a durable
user session.  Authorizing a purchase transaction would be an example of when
to use user authorization.

    ```php
    $authRequest = $client->auth()->authorize("LaunchKeyUserName");

    ```

A ```\LaunchKey\SDK\Domain\AuthRequest``` object will be returned to identify
the authorization request created for this authentication request.  The auth
request ID in this object will be used to identify this in the LaunchKey Engine
form this point forward.

## Determine if an authentication request is still authorized

You can determine the status of an auth request ID with a ```getStatus``` call:

    ```php
    $authResponse = $client->auth()->getStatus("authRequestId");

    ```

<a name="auth_response"/>
A ```\LaunchKey\SDK\Domain\AuthResponse``` object will be returned to represent
the current status of that authentication/authorization, or auth, request.
That object will contain the following data:

* Auth Request ID - The auth request its state represents.

* Completed - Has the user responded to the request.

* Authorized - How did the user response to the request.  NULL if completed is FALSE.

* User Hash - A unique identifier that identifies the LaunchKey user within the
 LaunchKey system regardless of a username change.  This value will be the best
 value to accurately identify a user across applications and username changes.

* Organization User ID - The identifier for this user in the organization in which
the application for the auth request exists.  This will be null if the
application does not belong to an organization.

* User Push ID - The identifying link between this user and the application
associated with the auth request.

* Device ID - A unique identifier for the device with which the user responded
to the request.

## De-orbit a user application session

When a user session, or orbit, was attained bu an authenticate request, a de-orbit
call is required to end that session, or de-orbit.

    ```php
    $client->auth()->deOrbit("AuthRequestID");

    ```

The de-orbit request has no return.

## Process a callback request

Callback requests allow you to process changes in state of an authorization
request in an asynchronous fashion.  By processing the post data received
by the endpoint specified in the app configuration, this can be accomplished:

    ```php
     $response = $client->auth()->handleCallback($_POST);

     ```

There are two different callback types that can be differentiated by the
object returned by the handle callback request:

### Auth Response

If configured, when a user responds to an auth request, an auth callback will be made.
The auth callback returns a ```\LaunchKey\SDK\Domain\AuthResponse``` object that
which is the <a href="#auth_response">same object returned by the ```getStatus``` call</a>.

### De-orbit

A user can initiate a de-orbit request remotely. If configured, the de-orbit callback is
how your application is informed of this event.  The de-orbit callback returns a
```\LaunchKey\SDK\Domain\DeOrbitCallback``` object.  The object identifies:

* User Hash - User hash of the user that performed the de-orbit.

* De-orbit Time - ```\DateTime``` object with the date/time the de-orbit request was made.
The de-orbit time should be used to determine if the de-orbit is still valid.  Due to the
asynchronous nature of callbacks, however unlikely, the user may have re-orbited
since the de-orbit request.  The de-orbit time can also be used to prevent replay attacks
that could prevent your users from accessing your system by continuously logging them
out by resending old de-orbit requests.

## Create a white label user

If you have a white label application, you will need to create users via an API call.
If your users use the LaunchKey Mobile application to accept auth requests, you will
not need this call and using it will trigger exceptions.

There currently is no direct way to create users for a white label group, you will need
to configure the client for an application that belongs to the white label group.

Creating a white label user is accomplished by passing an identifier for your PHP application
to the ```createUser``` method.  The identifier needs to be a permanent and unique identifier
of this user within your application. This identifier will be used authenticate the user as
well as pair devices additional devices to the user's account within your white label group.

    ```php
    $whiteLabelUser = $client->whiteLabel()->createUser($identifier);

    ```

The create user call returns a ```\LaunchKey\SDK\Domain\DeOrbitCallback``` object that
contains:

* QR Code URL - A URL to a QR code image that can be used to pair a device via a
white label mobile application.

* Code - An alphanumeric value that can be manually entered into a white label mobile
application to pair the device.

<a name="support"></a>
# Support

## GitHub

Submit feature requests and bugs on [GitHub](https://github.com/LaunchKey/launchkey-php/issues).

## Twitter

Submit a question to the Twitter Handle [@LaunchKeyHelp](https://twitter.com/LaunchKeyHelp).

## IRC

Engage the LaunchKey team in the `#launchkey` chat room on [freenode](https://freenode.net/).

## LaunchKey Help Desk

Browse FAQ's or submit a question to the LaunchKey support team for both
technical and non-technical issues. Visit the LaunchKey Help Desk [here](https://launchkey.desk.com/).
