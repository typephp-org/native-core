<?php

namespace TypePHP\NativeCore\Filesystem;

use TypePHP\NativeCore\Error\NativeCoreException;

final class LocalFilesystem implements Filesystem
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function read(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new NativeCoreException('Unable to read file: ' . $path);
        }
        return $contents;
    }

    public function write(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new NativeCoreException('Unable to write file: ' . $path);
        }
    }

    public function delete(string $path): void
    {
        if (file_exists($path) && !unlink($path)) {
            throw new NativeCoreException('Unable to delete file: ' . $path);
        }
    }
}
