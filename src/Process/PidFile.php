<?php

namespace TypePHP\NativeCore\Process;

use TypePHP\NativeCore\Error\NativeCoreException;

final class PidFile
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function writeCurrent(): void
    {
        $written = file_put_contents($this->path, (string) getmypid());
        if ($written === false) {
            throw new NativeCoreException('Unable to write PID file: ' . $this->path);
        }
    }

    public function remove(): void
    {
        if (is_file($this->path) && !unlink($this->path)) {
            throw new NativeCoreException('Unable to remove PID file: ' . $this->path);
        }
    }
}
