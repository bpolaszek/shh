[![Latest Stable Version](https://poser.pugx.org/bentools/shh/v/stable)](https://packagist.org/packages/bentools/shh)
[![License](https://poser.pugx.org/bentools/shh/license)](https://packagist.org/packages/bentools/shh)
[![Build Status](https://img.shields.io/travis/bpolaszek/shh/master.svg?style=flat-square)](https://travis-ci.org/bpolaszek/shh)
[![Coverage Status](https://coveralls.io/repos/github/bpolaszek/shh/badge.svg?branch=master)](https://coveralls.io/github/bpolaszek/shh?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/bpolaszek/shh.svg?style=flat-square)](https://scrutinizer-ci.com/g/bpolaszek/shh)
[![Total Downloads](https://poser.pugx.org/bentools/shh/downloads)](https://packagist.org/packages/bentools/shh)

# Shh! 🤫

Shh! is a proof-of-concept aiming at dealing with secrets within your Symfony application.

## Why?

I was just reading [Storing secrets for Symfony applications](https://www.webfactory.de/blog/storing-secrets-for-symfony-applications) from [Matthias Pigulla](https://github.com/mpdude) which came with a solution using a Ruby-powered external program.

Then I came up with the following question: why isn't there a PHP implementation of this? 🤔

Here are the key principles:

* Storing secrets in environment variables will actually expose them through `phpinfo()`, reports, logs, and child processes.
* Thanks to Symfony's [Env Var Processors](https://symfony.com/doc/current/configuration/env_var_processors.html), _Shh_ will expose them **encrypted**. They will be decrypted at the very last moment.
* Private key + an optional passphrase are required to decrypt secrets. They SHOULD be _.gitgnored_.
* You can then commit encrypted secrets to VCS as long as the private key is stored and communicated safely.
* You can change your passphrase a at any time.

## Installation

```bash
composer require bentools/shh:0.3.*
```

## Configuration

* Add the bundle to your kernel (come on, you're not using Flex?). 
* Generate your keys:
    * Create a `shh` directory into your config directory `mkdir -p config/shh` (or `mkdir -p app/config/shh` for Symfony 3)
    * Run`php bin/console shh:generate:keys`
    * If you provided one, store the passphrase in the `SHH_PASSPHRASE` environment variable
    * Add `config/shh/private.pem` (or `app/config/shh/private.pem` for Symfony 3) to your `.gitignore` and upload it to your production server.

**And you're ready to go!** 

If you want a different configuration, check out the [configuration reference](#configuration-reference) to discover the available options.

## Usage

### Check the environment is properly configured

```bash
bin/console shh:check // Will check that encryption / decryption work - both private and public keys are needed.
```

```bash
bin/console shh:check --encrypt-only // Will check that encryption works - only public key is needed?
```

### Encrypt a value (public key needed)

```bash
bin/console shh:encrypt
```

### Decrypt a value (public key + private key needed)

```bash
bin/console shh:decrypt
```

### Decrypt secrets in environment variables

This library ships with an environment variable processor. You can use it like this:

```yaml
# config/services.yaml
parameters:
    some_secret_thing: '%env(shh:SOME_ENCRYPTED_SECRET)%'

```

### Working with a secrets file

You can store your encrypted secrets in a `.secrets.json` file at the root of your project directory (you can set a different path in the `SHH_SECRETS_FILE` environment variable).

This file can safely be committed to VCS (as soon as the private key isn't).

To encrypt and register a secret in this file, run the following command:

```bash
bin/console shh:register:secret my_secret # You will be prompted for the value of "my_secret"
```

You can then use your secrets in your configuration files in the following way:

```yaml
# config/services.yaml
parameters:
    my_secret: '%env(shh:key:my_secret:json:file:SHH_SECRETS_FILE)%'

```

### Changing passphrase

You can change your passphrase if needed: this will result in a new private key being generated. The public key remains unchanged. 

```bash
bin/console shh:change:passphrase
```

As a result, a new private key will be regenerated. You just have to update it everywhere it is used,
and update the `SHH_PASSPHRASE` environment variable as well.

You may do this every time an employee leaves the company, for instance.

## Configuration reference

```yaml
# config/packages/shh.yaml
parameters:
    env(SHH_SECRETS_FILE): '%kernel.project_dir%/.secrets.json'

shh:
    private_key_file:     '%kernel.project_dir%/config/shh/private.pem'
    public_key_file:      '%kernel.project_dir%/config/shh/public.pem'
    passphrase:           '%env(SHH_PASSPHRASE)%'
```

## Tests

```bash
./vendor/bin/phpunit
```

## Feedback

Don't hesitate to ping me on Symfony Slack: **@bpolaszek**.

## License

MIT
