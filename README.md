# Shh! 🤫

Shh! is a proof-of-concept aiming at dealing with secrets within your Symfony application.

## Why?

I was just reading [Storing secrets for Symfony applications](https://www.webfactory.de/blog/storing-secrets-for-symfony-applications) from [Matthias Pigulla](https://github.com/mpdude) which came with a solution using a Ruby-powered external program.

Then I came up with the following question: why isn't there a PHP implementation of this? 🤔

Here are the key principles:

* Storing secrets in environment variables will expose them through `phpinfo()`, reports, logs, and child processes.
* Common encrypt/decrypt strategies require a _key_ or passphrase which works both ways. The problem is, as the key is needed for the application to work, any developer that has access to the project needs that key and can access any secret in plain text.
* The approach of Matthias is different: any developer can _encrypt_ secrets, but only the production server is able to _decrypt_ them.
* With that approach, the **public key** (needed to encrypt) is commited to VCS, while the **private key** remains property of the production server.
* Encrypted secrets can thus be committed to VCS. Only the production server will be able to read them.

## Installation

> composer require bentools/shh:1.0.x-dev

## Configuration

* Add the bundle to your kernel. 
* Create your keys:
    * Create the directory `mkdir .keys`
    * Generate the private key file: `openssl genrsa -out .keys/private.pem` 
    * Generate the public key file: `openssl rsa -pubout -in .keys/private.pem -out .keys/public.pem` 
    * Add `.keys/private.pem` to your `.gitignore` and upload it to your production server

* Alternatively, you can generate stronger keys with a passphrase:
    * `openssl genrsa -out .keys/private.pem -aes256 4096` 
    * `openssl rsa -pubout -in .keys/private.pem -out .keys/public.pem` 
    * Store the passphrase in the `SHH_PASSPHRASE` environment variable

**And you're ready to go!** 

If you want a different configuration, run `bin/console config:dump-reference shh` to find out the available options.

## Usage

### Check the environment is properly configured

```bash
bin/console shh:check -h
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

## Tests

Needed. I know.

## Feedback

Don't hesitate to ping me on Symfony Slack: **@bpolaszek**.

## License

MIT
