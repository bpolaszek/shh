[![Latest Stable Version](https://poser.pugx.org/bentools/shh/v/stable)](https://packagist.org/packages/bentools/shh)
[![License](https://poser.pugx.org/bentools/shh/license)](https://packagist.org/packages/bentools/shh)
[![CI Workflow](https://github.com/bpolaszek/shh/actions/workflows/ci.yml/badge.svg)](https://github.com/bpolaszek/shh/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/bpolaszek/shh/branch/master/graph/badge.svg?token=4E762WVKSA)](https://codecov.io/gh/bpolaszek/shh)[![Quality Score](https://img.shields.io/scrutinizer/g/bpolaszek/shh.svg?style=flat-square)](https://scrutinizer-ci.com/g/bpolaszek/shh)
[![Total Downloads](https://poser.pugx.org/bentools/shh/downloads)](https://packagist.org/packages/bentools/shh)

# Shh! 🤫

Shh! is a simple library to deal with secrets. It helps you generate key pairs, encrypt/decrypt a payload, store secrets in a safe way.

For the full background behind this, see the [Symfony Bundle documentation](https://github.com/bpolaszek/shh-bundle)

## Installation

```bash
composer require bentools/shh:^1.0
```

## Usage

### Generate keys
```php
use BenTools\Shh\Shh;

[$publicKey, $privateKey] = Shh::generateKeyPair();
```

By default `sha512` algorithm is used with a length of 4096 bits.

Example with a passphrase and a different configuration:

```php
use BenTools\Shh\Shh;

[$publicKey, $privateKey] = Shh::generateKeyPair('Some passphrase', ['private_key_bits' => 512, 'digest_alg' => 'sha256']);
```

### Change passphrase

You can change the passphrase of an existing key:

```php
use BenTools\Shh\Shh;

[$publicKey, $privateKey] = Shh::generateKeyPair();
$privateKey = Shh::changePassphrase($privateKey, null, 'now I have a passphrase');
```

This generates a new private key.

The public key remains unchanged, and existing secrets can still be decoded, with the new passphrase only.

### Encrypt / decrypt secrets

Public key is required to encrypt secrets, while public **AND** private keys are required to decode them.

```php
use BenTools\Shh\Shh;

$shh = new Shh($publicKey, $privateKey);
$encoded = $shh->encrypt('foo');
$decoded = $shh->decrypt($encoded);
```

Payloads are serialized/deserialized using base64.

### Secret storage

It allows you to store encrypted secrets. You can safely publish a file containing secrets as soon as the private key is not published.

Only the owners of the private key (and its associated passphrase, if any) will be able to decrypt the secrets in it.

```php
use BenTools\Shh\SecretStorage\JsonFileSecretStorage;
use BenTools\Shh\Shh;
[$publicKey, $privateKey] = Shh::generateKeyPair('Some passphrase', ['private_key_bits' => 512, 'digest_alg' => 'sha256']);

$shh = new Shh($publicKey, $privateKey);
$storage = new JsonFileSecretStorage($shh, './secrets.json');
$storage->store('some-secret');
$storage->has('some-secret');
$storage->get('some-secret'); // Reveal
$storage->getKeys(); // List known secrets
```

## Tests

```bash
./vendor/bin/phpunit
```

## License

MIT
