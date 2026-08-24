<?php

namespace TypePHP\NativeCore\Process;

use TypePHP\NativeCore\Error\NativeCoreException;

final class SingleInstance
{
    private ProcessLock $lock;

    public function __construct(ProcessLock $lock)
    {
        $this->lock = $lock;
    }

    public function acquireOrFail(): void
    {
        if (!$this->lock->acquire()) {
            throw new NativeCoreException('Another instance already holds the process lock');
        }
    }

    public function release(): void
    {
        $this->lock->release();
    }
}
