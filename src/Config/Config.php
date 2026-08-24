<?php

namespace TypePHP\NativeCore\Config;

interface Config
{
    public function has(string $key): bool;

    public function string(string $key, string $default): string;

    public function integer(string $key, int $default): int;

    public function boolean(string $key, bool $default): bool;
}
