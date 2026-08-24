<?php

namespace TypePHP\NativeCore\Process;

interface ProcessLock
{
    public function acquire(): bool;

    public function release(): void;
}
