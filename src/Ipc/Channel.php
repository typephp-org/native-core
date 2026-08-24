<?php

namespace TypePHP\NativeCore\Ipc;

interface Channel
{
    public function send(string $message): void;

    public function hasMessage(): bool;

    public function receive(): string;
}
