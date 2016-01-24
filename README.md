# silex-user-pack
Silex User Pack provides a basic user [security provider](http://silex.sensiolabs.org/doc/providers/security.html) with an overridable login form.

It was inspired by [Silex SimpleUser](https://github.com/jasongrimes/silex-simpleuser)

It's based on Silex 2.x [security provider doc](https://github.com/silexphp/Silex/blob/master/doc/providers/security.rst).

## Installation

    composer require quazardous/silex-user-pack

## Usage

See [Silex pack](https://github.com/quazardous/silex-pack) for more informations on how to use packs.

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

The `user.login` is automatically created. Its name is derived from the full mounted `login_path` (all `/` are replaced with `_` and the leading `/` is stripped and then prefixed with the user pack's decamelize short namespace plus `.`).


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
            // 'login_path' => '/login', // default
            // 'check_path' => '/check_login', // default, prefixed with 'secured_mount_prefix'
            // 'logout_path' => '/lougout', // default, prefixed with 'secured_mount_prefix'
            // 'invalidate_session' => true, // default
        ],
    ]    
]);

...
```

NB: user pack should be mounted on `/` (option `user.mount_prefix`, see [Configurable Pack](https://github.com/quazardous/silex-pack#configurable-pack));

## Options
NB: the 'user.' prefix is relative to the pack name.

- `user.firewalls`

A list of firewalls to manage with Silex User Pack. 
Silex User Pack will try to guess the firewall authentication provider and inject required config.
It knows how to manage `form` and for the other just injects the `users` user provider.
For `form` you have to put at least an empty `form` entry (See above). User pack will try to set up `logout` as well.

- `user.unsecure_mount_prefix`

User pack will use this option to mount the 'login_path'. Default to '/'.

## Demo
Go clone the [Silex pack demo](http://github.com/quazardous/silex-pack-demo).
