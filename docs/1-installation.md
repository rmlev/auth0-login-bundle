Step 1: Install the bundle
==========================
### A) Install the bundle via Composer:

```bash
composer require rmlev/auth0-login-bundle
```

### B) Error message during installation

Installation error message occurs after installation:
```
The child config "domain" under "rmlev_auth0_login" must be configured: The URL of your Auth0 tenant domain
```

The bundle is installed but not configured.
It means that you should [configure the Auth0 application](2-configure_the_auth0_application.md).

After that you should run:
```bash
bin/console cache:clear
```

This will be fixed later.

### C) Make sure the bundle is enabled in the kernel:

```php
// config/bundles.php

public function registerBundles()
{
    $bundles = [
        // ...
        Rmlev\Auth0LoginBundle\RmlevAuth0LoginBundle::class => ['all' => true],
    ];
}
```

[Step 2: Configure the Auth0 application.](2-configure_the_auth0_application.md)

[Return to the index.](index.md)
