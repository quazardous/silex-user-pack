# silex-user-pack
Silex User Pack provides a basic user [security provider](http://silex.sensiolabs.org/doc/providers/security.html).

It was inspired by [Silex SimpleUser](https://github.com/jasongrimes/silex-simpleuser)

It's based on Silex 2.x [security provider doc](https://github.com/silexphp/Silex/blob/master/doc/providers/security.rst).

## Features

- login form
- register form with email verification
- lost password form

## Installation

    composer require quazardous/silex-user-pack

## Usage

See [Silex pack](https://github.com/quazardous/silex-pack) for more informations on how to use packs.

### Instantiate a PackableApplication
You have to extends a `Quazardous\Silex\PackableApplication` and use:
- `Silex\Application\TwigTrait`
- `Silex\Application\UrlGeneratorTrait`
- `Silex\Application\FormTrait`


```php
namespace Acme {
    class Application extends \Quazardous\Silex\PackableApplication {
        use \Silex\Application\TwigTrait;
        use \Silex\Application\UrlGeneratorTrait;
        use \Silex\Application\FormTrait;
    };
}

$app = new \Acme\Application;

```

### Setup Doctrine ORM

User pack has a default Doctrine ORM user provider.

```php
// provide Doctrine
$app->register(new \Silex\Provider\DoctrineServiceProvider, [
    ...
]);

// provide Doctrine ORM
$app->register(new \Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProviderDoctrineOrmServiceProvider, [
    ...
]);

```

### Setup Form

Symfony form component require validation and translation.

```php
...
$app->register(new \Silex\Provider\ValidatorServiceProvider());
// provide Symfony Doctrine Bridge for UniqueEntity validator
$app->register(new \Saxulum\DoctrineOrmManagerRegistry\Provider\DoctrineOrmManagerRegistryProvider());
$app->register(new \Silex\Provider\LocaleServiceProvider);
$app->register(new \Silex\Provider\TranslationServiceProvider(), [
    'locale' => 'fr',
    'locale_fallbacks' => ['en'],
]);
$app->register(new \Silex\Provider\CsrfServiceProvider());
$app->register(new \Silex\Provider\FormServiceProvider());
...
```

### Setup Security

User packs conforms with the security provider.

It will just try to make the setup *easier* and guess inject trivial logic in the security layer.

```php
// provide security
$app->register(new SecurityServiceProvider(), [
    'security.firewalls' => [
        'secured' => array(
            'pattern' => '^/admin/',
            'form' => [
                // user pack will populate the missing mandatory options but you have to set the 'form' key.
                // 'login_path' => '/login',
                // not specifying the login_path here means that user pack has to provide the path and the controller
                // 'check_path' => '/admin/login_check'
                // you can add all the custom scurity options you need
                'default_target_path' => '/admin',
                'failure_path' => '/',
            ], 
            'logout' => [
                // user pack will populate the missing mandatory options but you have to set the 'logout' key.
                //'logout_path' => '/admin/logout',
                //'invalidate_session' => true,
            ], 
            'users' => null, // if empty or not set, user pack will provide it for you with the built in Doctrine implementation.
        ),
    ],
]);

```

### Register User Pack

```php

// register the user pack wich tries to make the authentication easier...
// the user pack will try to complete the 'security.firewalls' for you.
// it will also provide a basic Doctrine ORM user provider and an overridable login form.
$app->register(new SilexUserPack(), [
    'user.firewalls' => [
        // one or more firewalls to manage, see below
        'secured' => [
            // 'secured_mount_prefix' => '/admin' // user pack will try to guess it from the 'pattern' key
            // you can specify non default values for:
            // 'login_path' => '/login', // default, prefixed with 'unsecured_mount_prefix'
            // 'check_path' => '/check_login', // default, prefixed with 'secured_mount_prefix'
            // 'logout_path' => '/logout', // default, prefixed with 'secured_mount_prefix'
            // 'invalidate_session' => true, // default
            // 'register_path' => '/register', // default, prefixed with 'unsecured_mount_prefix'
            // 'recover_password_path' => '/recover_password', // default, prefixed with 'unsecured_mount_prefix'
            // 'mailer_from' => '', // default, the from for the messages sent for registration
        ],
    ]    
]);

...
```

The route `user.login` is automatically created. Its name is derived from the full mounted `login_path` (all `/` are replaced with `_` and the leading `/` is stripped and then prefixed with the user pack's decamelize short namespace plus `.`).

The route `user.register` is automatically created. Its name is derived from the full mounted `register_path` (all `/` are replaced with `_` and the leading `/` is stripped and then prefixed with the user pack's decamelize short namespace plus `.`).

NB: user pack should be mounted on `/` (option `user.mount_prefix`, see [Optionnable Pack](https://github.com/quazardous/silex-pack#optionnable-pack));

## Options
NB: the `user.` prefix is derived from to the pack name (decamelize short namespace).

### `user.firewalls`

`user.firewalls` is a list of firewalls to manage with Silex User Pack.

```php
...
'user.firewalls' => [
        // one or more firewalls to manage
        'secured' => [/* specific options see below */]
        ],
...
```
Silex User Pack will try to guess the firewall authentication provider and inject required config.
It knows how to manage `form` and for the other just injects the `users` user provider.
For `form` you have to put at least an empty `form` entry (See above). User pack will try to set up `logout` as well.

- `unsecured_mount_prefix`: default to `/`
- `secured_mount_prefix`: by default we guess it from the `pattern` key
- `login_path`: default to `/login`, prefixed with `unsecured_mount_prefix`
- `check_path`: default to `/check_login`, prefixed with `secured_mount_prefix`
- `logout_path`: default to `/logout`, prefixed with `secured_mount_prefix`
- `register_path`: default to `/register`, prefixed with `unsecured_mount_prefix`, can be `false` to not handle registration
- `recover_password_path`: default to `/recover_password`, prefixed with `unsecured_mount_prefix`
- `use_email_as_username`: default to `false`, if `true` the registration form will not ask for a username

### Other options

See `$app['user.default.options']`

- `user.user_entity_class`: the class used for the user entity



## Demo
Go clone the [Silex pack demo](http://github.com/quazardous/silex-pack-demo).
