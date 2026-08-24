<?php

namespace TypePHP\NativeCore\Signals;

final class NoopSignalSource implements SignalSource
{
    public function install(): void
    {
    }

    public function stopRequested(): bool
    {
        return false;
    }
}
