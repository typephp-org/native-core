<?php

namespace TypePHP\NativeCore\Application;

final class NativeApplication
{
    public static function configure(): ApplicationBuilder
    {
        return new ApplicationBuilder();
    }

    private function __construct()
    {
    }
}
