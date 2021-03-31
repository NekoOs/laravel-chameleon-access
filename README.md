laravel-chameleon-access

# Installing

You can install the package via composer:

```bash
composer require spatie/laravel-permission
```

Optional: The service provider will automatically get registered. Or you may manually add the service provider in your config/app.php file:

```php
'providers' => [
    // ...
    NekoOs\ChameleonAccess\Providers\Initialize::class,
];
```

You should publish the migration and the config/permission.php config file with:

```bash
php artisan vendor:publish --provider="NekoOs\ChameleonAccess\Providers\Initialize"
```

## Usage

First, add the NekoOs\ChameleonAccess\HasScopes trait to your User model(s):

```php
use NekoOs\ChameleonAccess\HasScopes;

class User
{
    use HasScopes;

    // ...
}
```

## Assigning Roles

A role can be assigned to any user by a scope:

```php
$user->withScopeAssignRoles($model, 'writer');
```
