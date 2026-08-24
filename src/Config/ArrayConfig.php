<?php

namespace TypePHP\NativeCore\Config;

final class ArrayConfig implements Config
{
    /** @var array<string, mixed> */
    private array $values;

    /** @param array<string, mixed> $values */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function string(string $key, string $default): string
    {
        return $this->has($key) ? (string) $this->values[$key] : $default;
    }

    public function integer(string $key, int $default): int
    {
        return $this->has($key) ? (int) $this->values[$key] : $default;
    }

    public function boolean(string $key, bool $default): bool
    {
        return $this->has($key) ? (bool) $this->values[$key] : $default;
    }
}
