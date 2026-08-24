<?php

namespace TypePHP\NativeCore\Signals;

interface SignalSource
{
    public function install(): void;

    public function stopRequested(): bool;
}
