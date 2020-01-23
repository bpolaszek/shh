<?php

namespace BenTools\Shh\SecretStorage;

interface SecretStorageInterface
{
    /**
     * @param bool $encrypt - Whether or not the secret must be encrypted first.
     */
    public function store(string $key, string $value, bool $encrypt = true): void;

    public function has(string $key): bool;

    /**
     * @param bool $decrypt - Whether or not the secret must be decrypted first.
     */
    public function get(string $key, bool $decrypt = true): ?string;

    /**
     * @return string[]
     */
    public function getKeys(): iterable;
}
