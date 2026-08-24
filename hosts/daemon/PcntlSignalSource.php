<?php

namespace TypePHP\NativeCore\Host\Daemon;

use TypePHP\NativeCore\Signals\SignalSource;

final class PcntlSignalSource implements SignalSource
{
    private bool $stopped;

    public function __construct()
    {
        $this->stopped = false;
    }

    public function install(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, [$this, 'handle']);
        pcntl_signal(SIGTERM, [$this, 'handle']);
    }

    public function handle(int $signal): void
    {
        $this->stopped = true;
    }

    public function stopRequested(): bool
    {
        return $this->stopped;
    }
}
