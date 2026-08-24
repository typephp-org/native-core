<?php

namespace TypePHP\NativeCore\Filesystem;

final class Path
{
    public static function join(string $left, string $right): string
    {
        return rtrim($left, '/\\') . DIRECTORY_SEPARATOR . ltrim($right, '/\\');
    }

    private function __construct()
    {
    }
}
