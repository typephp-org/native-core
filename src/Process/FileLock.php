<?php

namespace TypePHP\NativeCore\Process;

final class FileLock implements ProcessLock
{
    private string $path;
    private mixed $handle;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->handle = null;
    }

    public function acquire(): bool
    {
        if ($this->handle !== null) {
            return true;
        }
        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            return false;
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }
        $this->handle = $handle;
        return true;
    }

    public function release(): void
    {
        if ($this->handle !== null) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function __destruct()
    {
        $this->release();
    }
}
