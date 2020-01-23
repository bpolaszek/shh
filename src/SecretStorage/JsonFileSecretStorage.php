<?php

namespace BenTools\Shh\SecretStorage;

use BenTools\Shh\Shh;

final class JsonFileSecretStorage implements SecretStorageInterface
{
    /**
     * @var Shh
     */
    private $shh;

    /**
     * @var string
     */
    private $secretsFile;

    public function __construct(Shh $shh, string $secretsFile)
    {
        $this->shh = $shh;
        $this->secretsFile = $secretsFile;
    }

    /**
     * @return array
     */
    private function open(): array
    {
        if (false === \file_exists($this->secretsFile)) {
            $secrets = [];
        } else {
            $content = \file_get_contents($this->secretsFile);
            $secrets = '' === $content ? [] : \json_decode($content, true);
            if (\JSON_ERROR_NONE !== \json_last_error()) {
                throw new \RuntimeException('json_decode error: '.\json_last_error_msg());
            }
        }

        return $secrets;
    }

    /**
     * @inheritDoc
     */
    public function store(string $key, string $value, bool $encrypt = true): void
    {
        $secrets = $this->open();

        if (true === $encrypt) {
            $value = $this->shh->encrypt($value);
        }

        if (false === \file_put_contents($this->secretsFile, \json_encode(\array_replace($secrets, [$key => $value]), \JSON_PRETTY_PRINT))) {
            throw new \RuntimeException(\sprintf('Could not write to %s.', $this->secretsFile));
        }

        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new \RuntimeException('json_encode error: '.\json_last_error_msg());
        }
    }

    public function has(string $key): bool
    {
        return \in_array($key, $this->getKeys(), true);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, bool $decrypt = true): ?string
    {
        if (false === $decrypt) {
            return $this->open()[$key] ?? null;
        }

        $raw = $this->get($key, false);

        return null !== $raw ? $this->shh->decrypt($raw) : null;
    }

    /**
     * @inheritDoc
     */
    public function getKeys(): iterable
    {
        return \array_keys($this->open());
    }
}
