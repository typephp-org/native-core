<?php

namespace TypePHP\NativeCore\Ipc;

use TypePHP\NativeCore\Error\NativeCoreException;

final class InMemoryChannel implements Channel
{
    /** @var array<int, string> */
    private array $messages;

    public function __construct()
    {
        $this->messages = [];
    }

    public function send(string $message): void
    {
        $this->messages[] = $message;
    }

    public function hasMessage(): bool
    {
        return count($this->messages) > 0;
    }

    public function receive(): string
    {
        if (!$this->hasMessage()) {
            throw new NativeCoreException('The IPC channel is empty');
        }
        return (string) array_shift($this->messages);
    }
}
