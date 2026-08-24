<?php

namespace TypePHP\NativeCore\Filesystem;

interface Filesystem
{
    public function exists(string $path): bool;

    public function read(string $path): string;

    public function write(string $path, string $contents): void;

    public function delete(string $path): void;
}
