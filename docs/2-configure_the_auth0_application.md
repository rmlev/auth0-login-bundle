Step 2: Configure the Auth0 application
=======================================

After installing the bundle, you have to set Auth0 application options in the configuration files.

To do this you should create these configuration file `config/packages/rmlev_auth0_routing.yaml/rmlev_auth0_login.yaml`.

### A) Add Auth0 application configuration

To configure, you should put into a .env file at the root of your project directory
the Auth0 application's environment variables.

#### Add Auth0 configuration to .env file:

```sh
# .env

# Your Auth0 application's Client ID
AUTH0_CLIENT_ID="..."

# The URL of your Auth0 tenant domain
AUTH0_DOMAIN="..."

# Your Auth0 application's Client Secret
AUTH0_CLIENT_SECRET="..."

# A long, secret value is used to encrypt the session cookie.
# This can be generated using `openssl rand -hex 32` from your shell.
AUTH0_COOKIE_SECRET=...
```

### B) Create `rmlev_auth0_login.yaml` file

You should create `rmlev_auth0_login.yaml` (the filename can be anything) file in the `config/packages` directory and put the configuration there:

```yaml
# config/packages/rmlev_auth0_routing.yaml

rmlev_auth0_login:
    client_id: "%env(AUTH0_CLIENT_ID)%"
    client_secret: "%env(AUTH0_CLIENT_SECRET)%"
    domain: "%env(resolve:AUTH0_DOMAIN)%"
    cookie_secret: "%env(resolve:AUTH0_COOKIE_SECRET)%"
```

### C) Import the routing

The bundle comes with necessary routes.
Import the `config/routes.xml` routing file from Auth0LoginBundle to your routing file to use these routes.
You should create `rmlev_auth0_routing.yaml` (the filename can be anything) file in the config/routes directory:

```yaml
# config/routes/rmlev_auth0_routing.yaml

_auth0:
    resource: '@RmlevAuth0LoginBundle/config/routes.xml'
    prefix: '/auth0'
```

You can define the prefix you need (/auth0 or whatever).

[Step 3: Configure the security layer.](3-configure_the_security_layer.md)

[Return to the index.](index.md)
