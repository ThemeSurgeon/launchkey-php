CHANGELOG for LaunchKey PHP SDK
===============================

This changelog references the relevant changes (bug and security fixes) for the lifetime of the library.

To get the diff for a specific change, go to https://github.com/LaunchKey/launchkey-node/commit/XXX where XXX is the
change hash.  To quickly get the diff between two versions, you can use the GitHub compare endpoint.  For example,
[https://github.com/LaunchKey/launchkey-php/compare/v0.1.0...v0.1.1](https://github.com/LaunchKey/launchkey-php/compare/v0.1.0...v0.1.1)
shows the difference between the v0.1.0 and v0.1.1 tags.

* 1.0.0
    * Remove the last few lingering LK Identifier references.
* 1.0.0-beta2
    * Mitigate undefined index warning in poll call
* 1.0.0-beta1
    * Fixed numerous typos
    * Remove LK Identifier from white label user as it is no longer used. 
* 1.0.0-alpha3
    * Greatly improved API error code handling to allow for proper handling of:
        * No such user
        * Expired auth request
        * No paired devices
        * Rate limit exceeded
        * Engine error processing log call
* 1.0.0-alpha2
    * Bug fix for handleCallback method of auth service not returning an object as expected.
    * Terminology change for handleCallback regarding query string data versus post data.
        * Updated documentation to reflect using query string data in README and doc blocks
        * Updated variable names from postData to queryParameters to alleviate any confusion
* 1.0.0-alpha1
    * Refactor entire SDK for better usability and increased testing.
    * Added white label user service with create user functionality.
