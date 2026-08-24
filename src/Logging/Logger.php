<?php

namespace TypePHP\NativeCore\Logging;

interface Logger
{
    /** @param array<string, mixed> $context */
    public function log(string $level, string $message, array $context): void;
}
