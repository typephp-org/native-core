<?php

namespace TypePHP\NativeCore\Config;

final class Environment
{
    private string $name;
    private bool $debug;

    public function __construct(string $name, bool $debug)
    {
        $this->name = $name;
        $this->debug = $debug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }
}
