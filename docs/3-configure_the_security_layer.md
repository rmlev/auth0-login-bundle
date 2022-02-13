Step 3: Configure the security layer
====================================

To enable auth0 authenticator you should configure:

* a user provider **([A](#A-item))**
* the auth0_login option under a firewall **([B](#B-item))**
* entry point – optional **([C](#C-item))**

Security layer configuration starts with user provider configuration.

### <a name="A-item"></a> A) User provider must implement `Auth0AwareUserProviderInterface`
The implementation of `Rmlev\Auth0LoginBundle\Security\Core\User\Auth0AwareUserProviderInterface`
can load a user from Auth0 response.
If you need a custom user provider, your service has to implement the interface:<br>
`Rmlev\Auth0LoginBundle\Security\Core\User\Auth0AwareUserProviderInterface`.

Auth0LoginBundle ships with two built-in user providers:

- `Rmlev\Auth0LoginBundle\Security\Core\User\Auth0UserProvider` **([A.1.](#A1-item))**
- `Rmlev\Auth0LoginBundle\Security\Core\User\EntityUserProvider` **([A.2.](#A2-item))**

And accordingly, the bundle comes with two user provider factories
(factory with key **`auth0_memory`** for `Auth0UserProvider` and factory with key **`auth0_entity`** for `EntityUserProvider`).

#### <a name="A1-item"></a> A.1. `Auth0UserProvider` converts the data received from the Auth0 response to `Auth0User`.
This provider doesn't persist users. The bundle comes with `Rmlev\Auth0LoginBundle\Security\Core\User\Auth0User` class
to represent a user.

To use **`Auth0UserProvider`** you should set the **`auth0_memory`** option
under the security `providers` option.

The example of usage `Auth0UserProvider`:
```yaml
# config/packages/security.yaml
security:
    # ...
    providers:
        my_auth0_memory:
            auth0_memory:
                user_data_loader: ~
    # ...

    firewalls:
        main:
            provider: my_auth0_memory
            # ...
            auth0_login:
                # ...
```

The `my_auth0_memory` is an arbitrary provider name.

You can set provider under `auth0_login` option to override the firewall-level provider option:
```yaml
# config/packages/security.yaml
security:
    # ...
    providers:
        my_auth0_memory:
            auth0_memory:
                user_data_loader:
                    default:
                        identifier: email
                        auth0_key: sub
                        map_options:
                            sub: auth0_user_key
                            picture: avatar
    # ...

    firewalls:
        main:
            # ...
            auth0_login:
                # ...
                provider: my_auth0_memory
                # ...
```

The `auth0_memory` user provider supports the following configuration options:
<br>
##### `user_data_loader`
type: array default: []

The `user_data_loader` has two configuration options underneath:

- `service`
- `default`

You can only set one of these two options (`service` or `default`) and can't use these options simultaneously.
<br>
##### `service`
type: string default: null

This key lets you configure which custom User data loader service will be used to load user data
from the Auth0 response. This service has to be an implementation of<br>
`Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoaderInterface`.

The example of configuration custom user_data_loader service:
```yaml
    # ...
    user_data_loader:
        service: my.security.user.data_loader
    # ...
```
`my.security.user.data_loader` is custom service that implement the interface<br>
`Rmlev\Auth0LoginBundle\ResponseLoader\ResponseUserDataLoaderInterface`.
<br>
##### `default`
type: array

The `default` option allows you to tweak the implementation of the `user_data_loader` service that comes with Auth0LoginBundle.
Under the `default` option, you can define the following: identifier property in a user class in Symfony security system,
user identifier on Auth0 server, and map data between Auth0 response data and a user class.
<br>
##### `identifier`
type: string

Defines property in `Auth0User` which is an identifier of a user. The default value in the implementation of
`ResponseUserDataLoaderInterface` provided by Auth0LoginBundle is `email`.
<br>
##### `auth0_key`
type: string

Defines user identifier in Auth0 response. The default value in the implementation of
`ResponseUserDataLoaderInterface` provided by Auth0LoginBundle is `sub`.
<br>
##### `map_options`
type: array

Defines the mapping data between Auth0 response data and a class that represents a user.

Default map between Auth0 response data and a user class:

| Auth0 response | User class     |
| -------------- | -------------- |
| nickname       | nickname       |
| name           | name           |
| email          | email          |
| sub            | auth0_user_key |
| picture        | avatar         |

Example:
```yaml
    # ...
    user_data_loader:
        default:
            identifier: email
            auth0_key: sub
            map_options:
                sub: auth0_user_key
                picture: avatar
    # ...
```

#### <a name="A2-item"></a> A.2. `EntityUserProvider` converts the data received from the Auth0 response to the Doctrine entity.
You can use **`EntityUserProvider`** (`Rmlev\Auth0LoginBundle\Security\Core\User\EntityUserProvider`)
if [DoctrineBundle](https://github.com/doctrine/DoctrineBundle) is installed.
This provider persists users in a database.
To use **`EntityUserProvider`** you should set the **`auth0_entity`** option underneath the
security `providers` option.

The example of usage `EntityUserProvider`:
```yaml
# config/packages/security.yaml
security:
    # ...
    providers:
        entity_users:
            auth0_entity:
                class: 'App\Entity\User'
                property: 'email'
    # ...

    firewalls:
        main:
            provider: entity_users
            # ...
            auth0_login:
                # ...
```

The `auth0_entity` user provider supports the following configuration options:
<br>
##### `class`
type: string default: null

Defines the class of the entity that represents users. This option is required.
<br>
##### `property`
type: string default: null

Defines the property to query by. This option is required.
<br>
##### `manager_name`
type: string default: null

If you're using multiple Doctrine entity managers, this option defines which one to use.
This is optional.

You can configure the ResponseUserDataLoader service in the `user_data_loader` option under the `auth0_login` key.
<br>
### <a name="B-item"></a> B) Enable the Auth0 authenticator using the `auth0_login` setting under a firewall

After configuring a user provider you can configure the `auth0_login` authenticator under a firewall.

This is the minimal configuration required to use the authenticator that comes with the Auth0LoginBundle:

```yaml
# config/packages/security.yaml
security:
    # ...

    firewalls:
        main:
            # ...
            auth0_login: ~
```

You can configure authentication settings under the `auth0_login` option:

```yaml
# config/packages/security.yaml
security:
    # ...

    firewalls:
        main:
            # ...
            auth0_login:
                check_path: auth0_callback
```
<br>
The `auth0_login` authenticator supports the following configuration options:

##### `check_path`
type: string default: auth0_callback

The `check_path` option defines a callback URL.
A callback URL is the URL that is invoked after Auth0 authorization for the consumer.
This URL should match "Allowed Callback URLs" in the Auth0 Applications dashboard.
The default `check_path` option value (`auth0_callback`) is defined in the bundle's `routes.xml` routing file.
If you need, you can create your route for a callback URL.
<br>
##### `login_path`
type: string default: /login

The `login_path` is used in the entry point to start the authentication process.
Auth0LoginBundle redirects the user on the `login_path` whenever an unauthenticated user tries to access a protected resource.
<br>
##### `logout_redirect_path`
type: string default: /

Defines URL to redirect after logout from Auth0 service.
This should match "Allowed Logout URLs" in the Auth0 Applications dashboard.
<br>
##### `user_data_loader`
type: array default: []

`auth0_login` has a `user_data_loader` option which allows overriding the `user_data_loader` option
defined in the provider.
<br>
##### `provider`
type: string default: null

This option defines a user provider for `auth0_login` authenticator.
<br>
### <a name="C-item"></a> C) Entry Point

If the firewall has multiple ways to authenticate (e.g. `form_login` and `auth0_login`), it is required
to configure the authentication entry point.
If you are using the new authenticator-based security system, you can set `auth0_login` to the `entry_point` option.

Example:
```yaml
# config/packages/security.yaml
security:
    enable_authenticator_manager: true
    # ...

    firewalls:
        main:
            # ...
            auth0_login:
                check_path: auth0_callback

            entry_point: auth0_login
```

[Return to the index.](index.md)
