# Amazon Cognito Provider for OAuth 2.0 Client
[![Latest Version](https://img.shields.io/github/release/CakeDC/oauth2-cognito.svg?style=flat-square)](https://github.com/CakeDC/oauth2-cognito/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/CakeDC/oauth2-cognito/master.svg?style=flat-square)](https://travis-ci.org/CakeDC/oauth2-cognito)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/CakeDC/oauth2-cognito.svg?style=flat-square)](https://scrutinizer-ci.com/g/CakeDC/oauth2-cognito/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/CakeDC/oauth2-cognito.svg?style=flat-square)](https://scrutinizer-ci.com/g/CakeDC/oauth2-cognito)
[![Total Downloads](https://img.shields.io/packagist/dt/CakeDC/oauth2-cognito.svg?style=flat-square)](https://packagist.org/packages/CakeDC/oauth2-cognito)

This package provides Amazon Cognito OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require cakedc/oauth2-cognito
```

## Usage

Usage is the same as The League's OAuth client, using `\CakeDC\OAuth2\Client\Provider\Cognito` as the provider.

### Authorization Code Flow

```php
$provider = new CakeDC\OAuth2\Client\Provider\Cognito([
    'clientId'          => '{cognito-client-id}',
    'clientSecret'      => '{cognito-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
    'cognitoDomain'     => '{cognito-client-domain}', 
    'region'            => '{cognito-region}' 
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getEmail());

    } catch (Exception $e) {

        // Failed to get user details
        exit(':(');
    }
}
```

### Managing Scopes

When creating your Amazon Cognito authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['email','phone','profile']
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the following scopes are available:

- phone
- email
- profile
- openid (required for phone, email or profile)
- aws.cognito.signin.user.admin

## Hosted domain

Optionally, if you are using your own domain for you client, you can configure it instead of `cognitoDomain` option when the provider is initialized:

```php
$provider = new CakeDC\OAuth2\Client\Provider\Cognito([
    'clientId'          => '{cognito-client-id}',
    'clientSecret'      => '{cognito-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
    'hostedDomain'      => '{cognito-hosted-domain}', //Full domain without trailing slash
]);

```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/cakedc/oauth2-cognito/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Cake Development Corporation](https://github.com/cakedc)
- [Alejandro Ibarra](https://github.com/ajibarra)
- [All Contributors](https://github.com/cakedc/oauth2-cognito/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/cakedc/oauth2-cognito/blob/master/LICENSE) for more information.