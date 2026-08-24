<?php

namespace TypePHP\NativeCore\Logging;

final class NullLogger implements Logger
{
    public function log(string $level, string $message, array $context): void
    {
    }
}
